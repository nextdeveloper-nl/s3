<?php

namespace NextDeveloper\S3\Services;

use NextDeveloper\S3\Services\AbstractServices\AbstractDepositLedgersService;

/**
 * This class is responsible from managing the data for DepositLedgers
 *
 * Class DepositLedgersService.
 *
 * @package NextDeveloper\S3\Database\Models
 */
class DepositLedgersService extends AbstractDepositLedgersService
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * Deposit ledger is append-only — updates are not allowed.
     */
    public static function update($id, array $data)
    {
        throw new \NextDeveloper\Commons\Exceptions\NotAllowedException(
            'Deposit ledger entries are immutable and cannot be updated.'
        );
    }

    /**
     * Deposit ledger is append-only — deletes are not allowed.
     */
    public static function delete($id)
    {
        throw new \NextDeveloper\Commons\Exceptions\NotAllowedException(
            'Deposit ledger entries are immutable and cannot be deleted.'
        );
    }

    /**
     * Record an upfront deposit for a WORM commitment.
     *
     * Pass $iamAccountId explicitly when calling from a background/admin context
     * where UserHelper::currentAccount() would return the wrong account.
     */
    public static function deposit(int $s3AccountId, int $s3WormCommitmentId, float $amount, int $retentionDays, ?int $iamAccountId = null): void
    {
        \NextDeveloper\S3\Database\Models\DepositLedgers::create([
            's3_account_id'         => $s3AccountId,
            's3_worm_commitment_id' => $s3WormCommitmentId,
            'iam_account_id'        => $iamAccountId ?? \NextDeveloper\IAM\Helpers\UserHelper::currentAccount()->id,
            'type'                  => 'deposit',
            'amount'                => $amount,
            'days_remaining'        => $retentionDays,
            'days_total'            => $retentionDays,
            'performed_by'          => 'system',
            'performed_at'          => now(),
        ]);
    }

    /**
     * Record a pro-rata refund when a GOVERNANCE commitment is cancelled.
     */
    public static function refund(int $s3AccountId, int $s3WormCommitmentId, float $amount, int $daysRemaining, int $daysTotal, string $reason = '', ?int $iamAccountId = null): void
    {
        \NextDeveloper\S3\Database\Models\DepositLedgers::create([
            's3_account_id'         => $s3AccountId,
            's3_worm_commitment_id' => $s3WormCommitmentId,
            'iam_account_id'        => $iamAccountId ?? \NextDeveloper\IAM\Helpers\UserHelper::currentAccount()->id,
            'type'                  => 'refund',
            'amount'                => $amount,
            'days_remaining'        => $daysRemaining,
            'days_total'            => $daysTotal,
            'notes'                 => $reason,
            'performed_by'          => \NextDeveloper\IAM\Helpers\UserHelper::me()->uuid ?? 'system',
            'performed_at'          => now(),
        ]);
    }

    /**
     * Record a forfeiture (e.g. COMPLIANCE commitment that cannot be refunded).
     */
    public static function forfeiture(int $s3AccountId, int $s3WormCommitmentId, float $amount, string $reason = '', ?int $iamAccountId = null): void
    {
        \NextDeveloper\S3\Database\Models\DepositLedgers::create([
            's3_account_id'         => $s3AccountId,
            's3_worm_commitment_id' => $s3WormCommitmentId,
            'iam_account_id'        => $iamAccountId ?? \NextDeveloper\IAM\Helpers\UserHelper::currentAccount()->id,
            'type'                  => 'forfeiture',
            'amount'                => $amount,
            'days_remaining'        => 0,
            'days_total'            => 0,
            'notes'                 => $reason,
            'performed_by'          => 'system',
            'performed_at'          => now(),
        ]);
    }
}