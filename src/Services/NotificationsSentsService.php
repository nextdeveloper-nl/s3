<?php

namespace NextDeveloper\S3\Services;

use Carbon\Carbon;
use NextDeveloper\S3\Database\Models\NotificationsSents;
use NextDeveloper\S3\Services\AbstractServices\AbstractNotificationsSentsService;

/**
 * This class is responsible from managing the data for NotificationsSents
 *
 * Class NotificationsSentsService.
 *
 * @package NextDeveloper\S3\Database\Models
 */
class NotificationsSentsService extends AbstractNotificationsSentsService
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * Check whether a notification of this type has already been sent this month.
     *
     * @param  int     $s3AccountId  Integer PK of the s3_accounts record
     * @param  string  $type         e.g. 'quota_warning', 'quota_exceeded'
     * @param  string  $yearMonth    Format: 'YYYY-MM' (defaults to current month)
     */
    public static function shouldSend(int $s3AccountId, string $type, ?string $yearMonth = null): bool
    {
        $month = $yearMonth ?? Carbon::now()->format('Y-m');

        return !NotificationsSents::where('s3_account_id', $s3AccountId)
            ->where('notification', $type)
            ->whereYear('month', substr($month, 0, 4))
            ->whereMonth('month', substr($month, 5, 2))
            ->exists();
    }

    /**
     * Record that a notification was sent so it is not re-sent this month.
     */
    public static function markSent(int $s3AccountId, string $type): void
    {
        NotificationsSents::create([
            's3_account_id' => $s3AccountId,
            'iam_account_id' => \NextDeveloper\IAM\Helpers\UserHelper::currentAccount()->id,
            'notification'  => $type,
            'month'         => Carbon::now()->startOfMonth(),
            'sent_at'       => now(),
        ]);
    }
}