<?php

namespace NextDeveloper\S3\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NextDeveloper\S3\Database\Models\Accounts;
use NextDeveloper\S3\Helpers\QuotaHelper;

/**
 * Sent once per month when an account reaches ≥ 80% of either quota.
 */
class QuotaWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private Accounts $account)
    {
    }

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $storagePct = round(QuotaHelper::getStoragePct($this->account), 1);
        $egressPct  = round(QuotaHelper::getEgressPct($this->account), 1);

        $storageUsedGb  = round(($this->account->storage_bytes_used ?? 0) / 1073741824, 2);
        $storageLimitGb = round(($this->account->quota_storage_bytes ?? 0) / 1073741824, 2);
        $egressUsedGb   = round(($this->account->egress_bytes_mo_used ?? 0) / 1073741824, 2);
        $egressLimitGb  = round(($this->account->quota_egress_bytes_mo ?? 0) / 1073741824, 2);

        $daysUntilReset = now()->endOfMonth()->diffInDays(now());

        return (new MailMessage())
            ->subject("Storage quota warning for {$this->account->slug}")
            ->greeting("Hello,")
            ->line("Your S3 account **{$this->account->slug}** is approaching its quota limits.")
            ->line("**Storage:** {$storageUsedGb} GB used of {$storageLimitGb} GB ({$storagePct}%)")
            ->line("**Monthly Egress:** {$egressUsedGb} GB used of {$egressLimitGb} GB ({$egressPct}%)")
            ->line("Your monthly egress quota resets in approximately {$daysUntilReset} day(s).")
            ->line('If you reach 100% of either quota, your account will be automatically blocked until a manual review.')
            ->line('Please contact support if you need your quota increased.');
    }
}
