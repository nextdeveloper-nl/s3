<?php

namespace NextDeveloper\S3\Services;

use Carbon\Carbon;
use NextDeveloper\Commons\Exceptions\NotAllowedException;
use NextDeveloper\S3\Database\Models\Buckets;
use NextDeveloper\S3\Database\Models\WormCommitments;
use NextDeveloper\S3\Helpers\WormHelper;
use NextDeveloper\S3\Services\AbstractServices\AbstractWormCommitmentsService;

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

        $bucket = Buckets::where('id', $data['s3_bucket_id'])->first();
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

        DepositLedgersService::deposit($model->s3_account_id, $model->id, $deposit, $retentionDays);

        AuditLogsService::log('worm.create', \NextDeveloper\IAM\Helpers\UserHelper::me()->uuid ?? 'system', [
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
        $model = WormCommitments::where('uuid', $uuid)->firstOrFail();

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
                'Pro-rata refund on GOVERNANCE cancellation'
            );
        }

        AuditLogsService::log('worm.cancel', \NextDeveloper\IAM\Helpers\UserHelper::me()->uuid ?? 'system', [
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
        WormCommitments::where('status', 'active')
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
                    's3_account_id'         => $commitment->s3_account_id,
                    's3_worm_commitment_id' => $commitment->id,
                ]);
            });
    }
}