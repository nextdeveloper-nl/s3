<?php

namespace NextDeveloper\S3\Helpers;

use NextDeveloper\S3\Database\Models\Accounts;

/**
 * Quota threshold calculations for S3 accounts.
 */
class QuotaHelper
{
    public const WARN_THRESHOLD = 80.0;
    public const BLOCK_THRESHOLD = 100.0;

    /**
     * Percentage of storage quota used (0–100+).
     */
    public static function getStoragePct(Accounts $account): float
    {
        if (!$account->quota_storage_bytes || $account->quota_storage_bytes <= 0) {
            return 0.0;
        }

        return ($account->storage_bytes_used / $account->quota_storage_bytes) * 100;
    }

    /**
     * Percentage of monthly egress quota used (0–100+).
     */
    public static function getEgressPct(Accounts $account): float
    {
        if (!$account->quota_egress_bytes_mo || $account->quota_egress_bytes_mo <= 0) {
            return 0.0;
        }

        return ($account->egress_bytes_mo_used / $account->quota_egress_bytes_mo) * 100;
    }

    public static function isStorageExceeded(Accounts $account): bool
    {
        return self::getStoragePct($account) >= self::BLOCK_THRESHOLD;
    }

    public static function isEgressExceeded(Accounts $account): bool
    {
        return self::getEgressPct($account) >= self::BLOCK_THRESHOLD;
    }

    /**
     * Either quota at or above the warning threshold.
     */
    public static function shouldWarn(Accounts $account): bool
    {
        return self::getStoragePct($account) >= self::WARN_THRESHOLD
            || self::getEgressPct($account) >= self::WARN_THRESHOLD;
    }

    /**
     * Either quota at or above the block threshold.
     */
    public static function shouldBlock(Accounts $account): bool
    {
        return self::isStorageExceeded($account) || self::isEgressExceeded($account);
    }

    /**
     * Human-readable reason string for a block action.
     */
    public static function blockReason(Accounts $account): string
    {
        $reasons = [];

        if (self::isStorageExceeded($account)) {
            $usedGb = round($account->storage_bytes_used / 1073741824, 2);
            $limitGb = round($account->quota_storage_bytes / 1073741824, 2);
            $reasons[] = "Storage quota exceeded ({$usedGb} GB used of {$limitGb} GB)";
        }

        if (self::isEgressExceeded($account)) {
            $usedGb = round($account->egress_bytes_mo_used / 1073741824, 2);
            $limitGb = round($account->quota_egress_bytes_mo / 1073741824, 2);
            $reasons[] = "Monthly egress quota exceeded ({$usedGb} GB used of {$limitGb} GB)";
        }

        return implode('; ', $reasons);
    }
}
