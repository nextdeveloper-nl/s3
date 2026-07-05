<?php

namespace NextDeveloper\S3\Services;

use Carbon\Carbon;
use NextDeveloper\IAM\Helpers\UserHelper;
use NextDeveloper\S3\Database\Models\BandwidthMonthlies;
use NextDeveloper\S3\Services\AbstractServices\AbstractBandwidthMonthliesService;

/**
 * This class is responsible from managing the data for BandwidthMonthlies
 *
 * Class BandwidthMonthliesService.
 *
 * @package NextDeveloper\S3\Database\Models
 */
class BandwidthMonthliesService extends AbstractBandwidthMonthliesService
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * Add egress bytes to the monthly total (upsert by account + month).
     *
     * @param  int     $s3AccountId
     * @param  int     $bytes
     * @param  string  $yearMonth  Format: 'YYYY-MM' (defaults to current month)
     */
    public static function addEgress(int $s3AccountId, int $bytes, ?string $yearMonth = null): void
    {
        $month = self::resolveMonth($yearMonth);
        self::upsert($s3AccountId, $month, 'egress_bytes', $bytes);
    }

    /**
     * Add ingress bytes to the monthly total (upsert by account + month).
     */
    public static function addIngress(int $s3AccountId, int $bytes, ?string $yearMonth = null): void
    {
        $month = self::resolveMonth($yearMonth);
        self::upsert($s3AccountId, $month, 'ingress_bytes', $bytes);
    }

    /**
     * Zero out all egress/ingress records for the given month (called on 1st of month).
     */
    public static function resetMonth(?string $yearMonth = null): void
    {
        $month = self::resolveMonth($yearMonth);

        BandwidthMonthlies::withoutGlobalScopes()
            ->whereYear('month', $month->year)
            ->whereMonth('month', $month->month)
            ->update(['egress_bytes' => 0, 'ingress_bytes' => 0]);
    }

    private static function upsert(int $s3AccountId, Carbon $month, string $column, int $bytes, ?int $iamAccountId = null): void
    {
        $record = BandwidthMonthlies::withoutGlobalScopes()
            ->where('s3_account_id', $s3AccountId)
            ->whereYear('month', $month->year)
            ->whereMonth('month', $month->month)
            ->first();

        if ($record) {
            $record->increment($column, $bytes);
            $record->update(['updated_at' => now()]);
        } else {
            // Resolve iam_account_id from the s3 account when not provided explicitly.
            if ($iamAccountId === null) {
                $account       = \NextDeveloper\S3\Database\Models\Accounts::withoutGlobalScopes()->find($s3AccountId);
                $iamAccountId  = $account?->iam_account_id ?? 0;
            }

            BandwidthMonthlies::create([
                's3_account_id'  => $s3AccountId,
                'iam_account_id' => $iamAccountId,
                'month'          => $month->startOfMonth(),
                'egress_bytes'   => $column === 'egress_bytes' ? $bytes : 0,
                'ingress_bytes'  => $column === 'ingress_bytes' ? $bytes : 0,
            ]);
        }
    }

    private static function resolveMonth(?string $yearMonth): Carbon
    {
        if ($yearMonth) {
            return Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth();
        }

        return Carbon::now()->startOfMonth();
    }
}