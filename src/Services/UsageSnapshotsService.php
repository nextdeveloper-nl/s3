<?php

namespace NextDeveloper\S3\Services;

use NextDeveloper\S3\Database\Models\Accounts;
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
}