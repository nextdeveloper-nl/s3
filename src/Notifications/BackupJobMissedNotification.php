<?php

namespace NextDeveloper\S3\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NextDeveloper\S3\Database\Models\BackupJobs;

/**
 * Sent when a backup job's schedule has fired with no completed run since —
 * the direct answer to "silent failure is the #1 problem" from the backup.agent
 * design research (see docs/backup.agent/overview.md). Deduplicated per job per
 * day via NotificationsSentsService, same mechanism as the quota notifications.
 */
class BackupJobMissedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private BackupJobs $job)
    {
    }

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject("Backup job \"{$this->job->name}\" has missed its schedule")
            ->greeting('Hello,')
            ->line("The backup job **{$this->job->name}** has not completed successfully since its last expected run.")
            ->line("Schedule: `{$this->job->schedule}`")
            ->line('This can mean the agent is offline, the machine is unreachable, or the backup itself is failing.')
            ->line('Please check the agent status and job run history in your dashboard.');
    }
}
