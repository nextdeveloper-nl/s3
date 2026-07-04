<?php

namespace NextDeveloper\S3\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use NextDeveloper\S3\Database\Models\Accounts;
use NextDeveloper\S3\Database\Models\BackupJobs;
use NextDeveloper\S3\Notifications\BackupJobMissedNotification;
use NextDeveloper\S3\Services\BackupJobRunsService;
use NextDeveloper\S3\Services\NotificationsSentsService;

/**
 * Run every 15 minutes via scheduler (s3:backup-agents-check-missed).
 *
 * Flags every enabled backup job whose schedule has fired with no completed
 * run since, and sends at most one notification per job per day — this is
 * the platform-side half of "backups should never fail silently"; the
 * agent-side half is BackupJobsService::assertScriptHasPreScript() rejecting
 * a script job with nothing to run in the first place.
 */
class CheckMissedBackupsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function handle(): void
    {
        $dayKey = now()->format('Y-m-d');

        BackupJobRunsService::getMissedJobs()->each(function (BackupJobs $job) use ($dayKey) {
            $notificationKey = 'backup_job_missed:' . $job->uuid;

            if (!NotificationsSentsService::shouldSend($job->iam_account_id, $notificationKey, $dayKey)) {
                return;
            }

            $account = Accounts::withoutGlobalScopes()
                ->where('iam_account_id', $job->iam_account_id)
                ->first();

            $account?->notify(new BackupJobMissedNotification($job));

            NotificationsSentsService::markSent($job->iam_account_id, $notificationKey);
        });
    }
}
