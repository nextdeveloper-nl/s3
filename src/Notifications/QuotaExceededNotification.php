<?php

namespace NextDeveloper\S3\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NextDeveloper\S3\Database\Models\Accounts;
use NextDeveloper\S3\Helpers\QuotaHelper;

/**
 * Sent once per month when an account is blocked due to quota overflow.
 */
class QuotaExceededNotification extends Notification implements ShouldQueue
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
        $storageExceeded = QuotaHelper::isStorageExceeded($this->account);
        $egressExceeded  = QuotaHelper::isEgressExceeded($this->account);

        $storageUsedGb  = round(($this->account->storage_bytes_used ?? 0) / 1073741824, 2);
        $storageLimitGb = round(($this->account->quota_storage_bytes ?? 0) / 1073741824, 2);
        $egressUsedGb   = round(($this->account->egress_bytes_mo_used ?? 0) / 1073741824, 2);
        $egressLimitGb  = round(($this->account->quota_egress_bytes_mo ?? 0) / 1073741824, 2);

        $daysUntilReset = now()->endOfMonth()->diffInDays(now());

        $message = (new MailMessage())
            ->subject("Account blocked: storage quota exceeded for {$this->account->slug}")
            ->greeting('Important: Your account has been blocked')
            ->line("Your S3 account **{$this->account->slug}** has been automatically blocked because it exceeded its quota.");

        if ($storageExceeded) {
            $pct = round(QuotaHelper::getStoragePct($this->account), 1);
            $message->line("**Storage quota exceeded:** {$storageUsedGb} GB used of {$storageLimitGb} GB ({$pct}%)");
        }

        if ($egressExceeded) {
            $pct = round(QuotaHelper::getEgressPct($this->account), 1);
            $message->line("**Monthly egress quota exceeded:** {$egressUsedGb} GB used of {$egressLimitGb} GB ({$pct}%)");
            $message->line("Egress quota resets automatically on the 1st of each month (in approximately {$daysUntilReset} day(s)).");
        }

        $message
            ->line('**What this means:** All S3 operations (uploads, downloads, API calls) are currently blocked.')
            ->line('**To unblock your account:** Contact support and request a manual review or quota increase.')
            ->line('Your data is safe and will not be deleted.');

        return $message;
    }
}
