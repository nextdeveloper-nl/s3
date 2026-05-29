<?php

namespace NextDeveloper\S3\Helpers;

use Carbon\Carbon;
use NextDeveloper\S3\Database\Models\WormCommitments;

/**
 * Deposit and refund calculations for WORM commitments.
 */
class WormHelper
{
    private const BYTES_PER_GB = 1073741824;

    /**
     * Calculate the upfront deposit for a WORM commitment.
     *
     * @param  int    $quotaBytes     Committed storage quota in bytes
     * @param  float  $pricePerGbMo  Monthly price per GB
     * @param  int    $retentionDays Number of days locked
     * @return float  Deposit amount
     */
    public static function calculateDeposit(int $quotaBytes, float $pricePerGbMo, int $retentionDays): float
    {
        $quotaGb = $quotaBytes / self::BYTES_PER_GB;
        $months = $retentionDays / 30;

        return round($quotaGb * $pricePerGbMo * $months, 4);
    }

    /**
     * Calculate a pro-rata refund for a cancelled GOVERNANCE commitment.
     *
     * Formula: deposit × (days_remaining / retention_days)
     *
     * Returns 0 if commitment has already passed its lock date.
     */
    public static function calculateRefund(WormCommitments $commitment): float
    {
        if (!$commitment->locks_until || !$commitment->deposit_amount || !$commitment->retention_days) {
            return 0.0;
        }

        $now = Carbon::now();
        $locksUntil = Carbon::parse($commitment->locks_until);

        if ($now->greaterThanOrEqualTo($locksUntil)) {
            return 0.0;
        }

        $daysRemaining = $now->diffInDays($locksUntil, false);

        if ($daysRemaining <= 0) {
            return 0.0;
        }

        $refund = $commitment->deposit_amount * ($daysRemaining / $commitment->retention_days);

        return round(max(0.0, $refund), 4);
    }
}
