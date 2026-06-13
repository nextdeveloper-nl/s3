<?php

namespace NextDeveloper\S3\Services;

use Carbon\Carbon;
use NextDeveloper\Commons\Exceptions\NotAllowedException;
use NextDeveloper\S3\Database\Models\Buckets;
use NextDeveloper\S3\Database\Models\WormCommitments;
use NextDeveloper\S3\Helpers\WormHelper;
use NextDeveloper\S3\Services\AbstractServices\AbstractWormCommitmentsService;
use NextDeveloper\IAM\Helpers\UserHelper;

/**
 * This class is responsible from managing the data for WormCommitments
 *
 * Class WormCommitmentsService.
 *
 * @package NextDeveloper\S3\Database\Models
 */
class WormCommitmentsService extends AbstractWormCommitmentsService
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    // -------------------------------------------------------------------------
    // Query helpers
    // -------------------------------------------------------------------------

    /**
     * Return the current active commitment for a bucket, or null when none exists.
     */
    public static function getActiveForBucket(int $s3BucketId): ?WormCommitments
    {
        return WormCommitments::withoutGlobalScopes()
            ->where('s3_bucket_id', $s3BucketId)
            ->where('status', 'active')
            ->latest('committed_at')
            ->first();
    }

    // -------------------------------------------------------------------------
    // Lifecycle helpers called by BucketsService
    // -------------------------------------------------------------------------

    /**
     * Create a commitment directly from an already-persisted WORM bucket record.
     * Called by BucketsService immediately after worm_bucket_create is dispatched.
     */
    public static function createFromBucket(Buckets $bucket): WormCommitments
    {
        return static::create([
            's3_bucket_id'  => $bucket->id,
            's3_account_id' => $bucket->s3_account_id,
            'iam_account_id' => $bucket->iam_account_id,
            'mode'          => $bucket->object_lock_mode ?? 'COMPLIANCE',
            'retention_days' => (int) ($bucket->object_lock_days ?? 1),
            'quota_bytes'   => 0,
        ]);
    }

    /**
     * Supersede the active commitment and create a new one after a policy update.
     *
     * Rules enforced here:
     *  - COMPLIANCE mode: retention_days can only grow (never shrink).
     *  - If nothing changed, no new commitment is written (returns null).
     */
    public static function supersede(Buckets $bucket): ?WormCommitments
    {
        $current      = static::getActiveForBucket($bucket->id);
        $newDays      = (int) ($bucket->object_lock_days ?? 1);
        $newMode      = strtoupper($bucket->object_lock_mode ?? 'COMPLIANCE');

        if ($current) {
            // Idempotent — skip if nothing meaningful changed
            if ($current->retention_days === $newDays && strtoupper($current->mode) === $newMode) {
                return null;
            }

            // COMPLIANCE forbids shortening the retention period
            if (strtoupper($current->mode) === 'COMPLIANCE' && $newDays < $current->retention_days) {
                throw new NotAllowedException(
                    "COMPLIANCE retention period cannot be shortened (current: {$current->retention_days} days)."
                );
            }

            $current->update(['status' => 'superseded']);
        }

        return static::createFromBucket($bucket);
    }

    // -------------------------------------------------------------------------
    // Core lifecycle
    // -------------------------------------------------------------------------

    /**
     * Create a WORM commitment for a bucket.
     *
     * Validates that the bucket has Object Lock enabled, calculates the deposit,
     * persists the commitment, and records the deposit in the ledger.
     */
    public static function create(array $data)
    {
        if (empty($data['s3_bucket_id'])) {
            throw new NotAllowedException('A bucket ID is required for a WORM commitment.');
        }

        $bucket = Buckets::withoutGlobalScopes()->where('id', $data['s3_bucket_id'])->first();
        if (!$bucket || !$bucket->object_lock_enabled) {
            throw new NotAllowedException('The target bucket must have Object Lock enabled before creating a WORM commitment.');
        }

        $pricePerGbMo = $data['price_per_gb_mo'] ?? config('s3.worm.price_per_gb_mo', 0.023);
        $quotaBytes   = $data['quota_bytes'] ?? 0;
        $retentionDays = (int) ($data['retention_days'] ?? 365);

        $deposit = WormHelper::calculateDeposit($quotaBytes, (float) $pricePerGbMo, $retentionDays);

        $data['deposit_amount']  = $deposit;
        $data['price_per_gb_mo'] = $pricePerGbMo;
        $data['status']          = 'active';
        $data['committed_at']    = now();
        $data['locks_until']     = Carbon::now()->addDays($retentionDays);

        $model = parent::create($data);

        // Pass iam_account_id explicitly so the ledger entry is correct even
        // when this runs inside a runAsAdmin() background context.
        DepositLedgersService::deposit(
            $model->s3_account_id,
            $model->id,
            $deposit,
            $retentionDays,
            $model->iam_account_id ?: null,
        );

        AuditLogsService::log('worm.create', UserHelper::me()->uuid ?? 'system', [
            'iam_account_id'       => $model->iam_account_id,
            's3_account_id'        => $model->s3_account_id,
            's3_bucket_id'         => $model->s3_bucket_id,
            's3_worm_commitment_id' => $model->id,
            'deposit'              => $deposit,
        ]);

        return $model;
    }

    /**
     * Cancel a GOVERNANCE commitment and issue a pro-rata refund.
     * COMPLIANCE commitments cannot be cancelled.
     */
    public static function cancel(string $uuid): WormCommitments
    {
        $model = WormCommitments::withoutGlobalScopes()->where('uuid', $uuid)->firstOrFail();

        if (strtoupper($model->mode) === 'COMPLIANCE') {
            throw new NotAllowedException('COMPLIANCE WORM commitments cannot be cancelled.');
        }

        if ($model->status !== 'active') {
            throw new NotAllowedException("Cannot cancel a commitment with status '{$model->status}'.");
        }

        $refund = WormHelper::calculateRefund($model);
        $now = Carbon::now();
        $daysRemaining = max(0, (int) $now->diffInDays(Carbon::parse($model->locks_until), false));

        $model->update([
            'status'       => 'cancelled',
            'cancelled_at' => $now,
        ]);

        if ($refund > 0) {
            DepositLedgersService::refund(
                $model->s3_account_id,
                $model->id,
                $refund,
                $daysRemaining,
                $model->retention_days,
                'Pro-rata refund on GOVERNANCE cancellation',
                $model->iam_account_id ?: null,
            );
        }

        AuditLogsService::log('worm.cancel', UserHelper::me()->uuid ?? 'system', [
            'iam_account_id'        => $model->iam_account_id,
            's3_account_id'         => $model->s3_account_id,
            's3_worm_commitment_id' => $model->id,
            'refund'                => $refund,
        ]);

        return $model->fresh();
    }

    /**
     * Mark all commitments whose lock period has passed as expired.
     */
    public static function processExpired(): void
    {
        WormCommitments::withoutGlobalScopes()
            ->where('status', 'active')
            ->where('locks_until', '<', now())
            ->each(function (WormCommitments $commitment) {
                $commitment->update(['status' => 'expired', 'expired_at' => now()]);
            });
    }

    /**
     * Purge commitments that expired more than the configured grace period ago.
     * Default grace period: 30 days.
     */
    public static function processPurge(): void
    {
        $graceDays = config('s3.worm.purge_grace_days', 30);

        WormCommitments::where('status', 'expired')
            ->where('expired_at', '<', Carbon::now()->subDays($graceDays))
            ->each(function (WormCommitments $commitment) {
                $commitment->update(['status' => 'purged', 'purged_at' => now()]);

                AuditLogsService::log('worm.purge', 'system', [
                    'iam_account_id'        => $commitment->iam_account_id,
                    's3_account_id'         => $commitment->s3_account_id,
                    's3_worm_commitment_id' => $commitment->id,
                ]);
                // processExpired / processPurge use withoutGlobalScopes implicitly
                // via the each() iteration — no per-row reload needed.
            });
    }
}