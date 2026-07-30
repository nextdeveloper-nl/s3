<?php

namespace NextDeveloper\S3\Services;

use NextDeveloper\Commons\Exceptions\ModelNotFoundException;
use NextDeveloper\IAM\Helpers\UserHelper;
use NextDeveloper\S3\Database\Models\Accounts;
use NextDeveloper\S3\Database\Models\UsageSnapshots;
use NextDeveloper\S3\Helpers\QuotaHelper;
use NextDeveloper\S3\Services\AbstractServices\AbstractUsageSnapshotsService;

/**
 * This class is responsible from managing the data for UsageSnapshots
 *
 * Class UsageSnapshotsService.
 *
 * @package NextDeveloper\S3\Database\Models
 */
class UsageSnapshotsService extends AbstractUsageSnapshotsService
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * Snapshot current usage for an account and update its denormalised counters.
     */
    public static function snapshot(Accounts $account): void
    {
        \NextDeveloper\S3\Database\Models\UsageSnapshots::create([
            's3_account_id'  => $account->id,
            'iam_account_id' => $account->iam_account_id,
            'snapshot_at'    => now(),
            'storage_bytes'  => $account->storage_bytes_used ?? 0,
            'object_count'   => $account->object_count ?? 0,
        ]);

        $account->update(['usage_checked_at' => now()]);
    }

    /**
     * Take a snapshot, then evaluate and enforce quota thresholds.
     *
     * - ≥ 80%: send a warning notification (once per month, per type)
     * - ≥ 100%: send a block notification and block the account
     */
    public static function checkAndEnforce(Accounts $account): void
    {
        self::snapshot($account);

        $monthKey = now()->format('Y-m');

        if (QuotaHelper::shouldBlock($account)) {
            if (NotificationsSentsService::shouldSend($account->id, 'quota_exceeded', $monthKey)) {
                $account->notify(new \NextDeveloper\S3\Notifications\QuotaExceededNotification($account));
                NotificationsSentsService::markSent($account->id, 'quota_exceeded');
            }

            if ($account->status !== 'blocked') {
                AccountsService::block($account->uuid, QuotaHelper::blockReason($account));
            }

            return;
        }

        if (QuotaHelper::shouldWarn($account)) {
            if (NotificationsSentsService::shouldSend($account->id, 'quota_warning', $monthKey)) {
                $account->notify(new \NextDeveloper\S3\Notifications\QuotaWarningNotification($account));
                NotificationsSentsService::markSent($account->id, 'quota_warning');
            }
        }
    }

    /**
     * Daily-bucketed usage series for a single account, meant to feed a
     * usage-over-time graph. UsageSnapshots is written every 15 minutes
     * (CheckQuotasJob) and never pruned, so a raw range query would hand a
     * chart ~2,880 points for a 30-day window — this groups by calendar day
     * (Postgres date_trunc) and averages storage_bytes/object_count instead.
     *
     * $accountRef defaults to the caller's own S3 account (mirrors
     * BucketsService::create()'s auto-resolve) when omitted. When given
     * explicitly, both the Accounts and UsageSnapshots lookups go through
     * their default AuthorizationScope (no withoutGlobalScopes()), so a
     * non-privileged caller can't pull another account's series by passing
     * an arbitrary id/uuid — it just resolves to nothing.
     *
     * $from/$to are optional inclusive bounds on snapshot_at (any
     * Carbon-parseable string).
     */
    public static function getDailySeriesForAccount($accountRef = null, ?string $from = null, ?string $to = null): \Illuminate\Support\Collection
    {
        if ($accountRef) {
            $account = is_numeric($accountRef)
                ? Accounts::find((int) $accountRef)
                : Accounts::where('uuid', $accountRef)->first();
        } else {
            $iamAccount = UserHelper::currentAccount();
            $account = Accounts::where('iam_account_id', $iamAccount->id)->first();
        }

        if (!$account) {
            throw new ModelNotFoundException('S3 account not found.');
        }

        $query = UsageSnapshots::query()
            ->selectRaw("date_trunc('day', snapshot_at) as day, avg(storage_bytes) as storage_bytes, avg(object_count) as object_count")
            ->where('s3_account_id', $account->id)
            ->groupBy('day')
            ->orderBy('day');

        if ($from) {
            $query->where('snapshot_at', '>=', $from);
        }

        if ($to) {
            $query->where('snapshot_at', '<=', $to);
        }

        return $query->get();
    }
}