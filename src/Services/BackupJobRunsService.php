<?php

namespace NextDeveloper\S3\Services;

use Cron\CronExpression;
use Illuminate\Support\Collection;
use NextDeveloper\S3\Database\Models\BackupJobRuns;
use NextDeveloper\S3\Database\Models\BackupJobs;
use NextDeveloper\S3\Services\AbstractServices\AbstractBackupJobRunsService;

/**
 * This class is responsible from managing the data for BackupJobRuns
 *
 * Class BackupJobRunsService.
 *
 * @package NextDeveloper\S3\Database\Models
 */
class BackupJobRunsService extends AbstractBackupJobRunsService
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * Open a run record. Called for both schedule-driven runs (the agent's
     * own `result` envelope arrives without the platform ever having asked —
     * see BackupAgentEventService::handleResult()) and manual run_job_now
     * commands (BackupAgentCommandService::runJobNow()).
     */
    public static function startRun(BackupJobs $job, string $triggeredBy = 'schedule'): BackupJobRuns
    {
        return BackupJobRuns::create([
            's3_backup_job_id' => $job->id,
            'status'           => 'running',
            'started_at'       => now(),
            'triggered_by'     => $triggeredBy,
        ]);
    }

    public static function completeRun(string $uuid, array $output = []): BackupJobRuns
    {
        $run = BackupJobRuns::where('uuid', $uuid)->firstOrFail();

        $run->update([
            'status'            => 'completed',
            'finished_at'       => now(),
            'bytes_uploaded'    => $output['bytes_uploaded']    ?? null,
            'bytes_deduped'     => $output['bytes_deduped']     ?? null,
            'kopia_snapshot_id' => $output['kopia_snapshot_id'] ?? null,
        ]);

        return $run->fresh();
    }

    public static function failRun(string $uuid, ?string $error = null): BackupJobRuns
    {
        $run = BackupJobRuns::where('uuid', $uuid)->firstOrFail();

        $run->update([
            'status'      => 'failed',
            'finished_at' => now(),
            'error'       => $error,
        ]);

        return $run->fresh();
    }

    /**
     * Records a run the agent's own cron triggered, not a platform command.
     *
     * Jobs run agent-side on schedule (see docs/backup.agent/protocol.md) so
     * the platform never sees a "start" for these — the agent reports the
     * finished outcome in one `job_run` event, using its own uuid for the run
     * so a duplicate delivery is idempotent (Eloquent create() with an
     * explicit uuid just inserts that value; it isn't DB-generated here).
     */
    public static function recordRun(BackupJobs $job, array $payload): BackupJobRuns
    {
        $attributes = [
            's3_backup_job_id'  => $job->id,
            'status'            => $payload['status'] ?? 'completed',
            'started_at'        => $payload['started_at']  ?? null,
            'finished_at'       => $payload['finished_at'] ?? now(),
            'bytes_uploaded'    => $payload['bytes_uploaded']    ?? null,
            'bytes_deduped'     => $payload['bytes_deduped']     ?? null,
            'kopia_snapshot_id' => $payload['kopia_snapshot_id'] ?? null,
            'error'             => $payload['error'] ?? null,
            'triggered_by'      => 'schedule',
        ];

        if (!empty($payload['run_uuid'])) {
            $attributes['uuid'] = $payload['run_uuid'];
        }

        return BackupJobRuns::create($attributes);
    }

    /**
     * Enabled jobs whose schedule has fired with no completed run since —
     * the direct answer to "silent failure is the #1 problem" from the
     * design research. Uses dragonmantank/cron-expression (already a Laravel
     * dependency via Illuminate\Console\Scheduling) rather than a hand-rolled
     * "how long ago was this due" heuristic.
     *
     * Consumed by the s3:backup-agents-check-missed cron
     * (NextDeveloper\S3\Jobs\CheckMissedBackupsJob).
     */
    public static function getMissedJobs(): Collection
    {
        $graceMinutes = (int) config('s3.backup.missed_grace_minutes', 30);

        return BackupJobs::withoutGlobalScopes()
            ->where('is_enabled', true)
            ->whereNull('deleted_at')
            ->get()
            ->filter(function (BackupJobs $job) use ($graceMinutes) {
                try {
                    $cron = new CronExpression($job->schedule);
                } catch (\Exception) {
                    // Malformed schedule is a validation problem, not this cron's to police.
                    return false;
                }

                $expectedRun = $cron->getPreviousRunDate(now(), 0, true);
                $graceUntil  = (clone $expectedRun)->modify("+{$graceMinutes} minutes");

                if (now()->lt($graceUntil)) {
                    return false; // still inside the grace window
                }

                $hasCompletedSince = BackupJobRuns::withoutGlobalScopes()
                    ->where('s3_backup_job_id', $job->id)
                    ->where('status', 'completed')
                    ->where('created_at', '>=', $expectedRun)
                    ->exists();

                return !$hasCompletedSince;
            })
            ->values();
    }
}
