<?php

namespace NextDeveloper\S3\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use NextDeveloper\Commons\Services\CommentsService;
use NextDeveloper\S3\Database\Models\BackupAgents;
use NextDeveloper\S3\Database\Models\BackupJobRuns;
use NextDeveloper\S3\Database\Models\BackupJobs;
use NextDeveloper\S3\Database\Models\RestoreJobs;

/**
 * Sends commands to a backup.agent over NATS.
 *
 * Subject: agent.backup.{uuid}.cmd — envelope/operations documented in
 * docs/backup.agent/protocol.md.
 *
 * Dispatch goes through BackupAgents::sendAgentCommand() (NextDeveloper\Events\
 * Database\Traits\HasAgentCommands), which persists an event_agent_commands row
 * for audit/tracking in addition to publishing the NATS envelope - this now
 * works from queued-job/console contexts with no authenticated request (e.g.
 * BackupJobsService::syncAgent() after a job change), since
 * AgentCommandsService::dispatch() self-elevates via UserHelper::runAsAdmin()
 * when there's no current user.
 */
class BackupAgentCommandService
{
    /**
     * How many times we'll re-send run_job_now (each preceded by a full_sync,
     * see BackupAgentEventService::handleResult()) after the agent rejects it,
     * before giving up and escalating to a human via a system comment.
     */
    private const MAX_REJECTED_RETRIES = 3;

    /**
     * Push the full desired job list to an agent. Called after any job
     * create/update/delete and once as part of the registration response.
     */
    public static function fullSync(string $agentUuid): void
    {
        $agent = BackupAgents::withoutGlobalScopes()->where('uuid', $agentUuid)->first();

        $payload = $agent
            ? BackupJobsService::buildFullSyncPayload($agent)
            : ['jobs' => []];

        static::dispatch($agentUuid, 'full_sync', $payload);
    }

    /**
     * Trigger a job immediately, outside its normal schedule. Pre-creates the
     * `running` BackupJobRuns row here (unlike agent-scheduled runs, which the
     * agent reports after the fact via a `job_run` event) so the platform has
     * an auditable record from the moment the command is sent, not just from
     * when the agent gets around to replying.
     */
    public static function runJobNow(BackupJobs $job): BackupJobRuns
    {
        $run   = BackupJobRunsService::startRun($job, triggeredBy: 'manual');
        $agent = BackupAgents::withoutGlobalScopes()->find($job->s3_backup_agent_id);

        static::dispatch($agent->uuid, 'run_job_now', [
            'job_uuid' => $job->uuid,
            'run_uuid' => $run->uuid,
        ]);

        return $run;
    }

    /**
     * Called by BackupAgentEventService::handleResult() after it re-sends
     * full_sync in response to a `rejected` run_job_now result. The full_sync
     * should have caught the agent up on this job, so we retry the run — but
     * bounded: if the agent rejects the same job MAX_REJECTED_RETRIES times
     * in a row even after being re-synced each time, the problem isn't a
     * stale job list (full_sync already ruled that out repeatedly), so we
     * stop looping and leave a system comment on the job for a human instead
     * of retrying forever.
     *
     * The counter is cache-based, not a DB column — it's a transient loop
     * guard, not something worth persisting or a migration for.
     */
    public static function retryRunJobNowAfterRejection(BackupJobs $job): void
    {
        $cacheKey = "s3:backup-agent:run-job-now-rejections:{$job->uuid}";
        $attempts = (int) Cache::get($cacheKey, 0) + 1;

        if ($attempts > self::MAX_REJECTED_RETRIES) {
            Cache::forget($cacheKey);

            Log::error('[BackupAgentCommandService] run_job_now rejected repeatedly even after full_sync — escalating', [
                'job_uuid' => $job->uuid,
                'attempts' => $attempts - 1,
            ]);

            CommentsService::createSystemComment(
                "Backup job \"{$job->name}\" could not be started: the agent rejected run_job_now "
                    . (self::MAX_REJECTED_RETRIES) . " times in a row, even though the platform re-sent "
                    . 'full_sync before each retry. This is no longer a stale job-list problem — the agent '
                    . 'needs manual investigation.',
                $job
            );

            return;
        }

        Cache::put($cacheKey, $attempts, now()->addMinutes(15));

        static::runJobNow($job);
    }

    public static function pauseJob(BackupJobs $job): void
    {
        $agent = BackupAgents::withoutGlobalScopes()->find($job->s3_backup_agent_id);

        static::dispatch($agent->uuid, 'pause_job', ['job_uuid' => $job->uuid]);
    }

    public static function resumeJob(BackupJobs $job): void
    {
        $agent = BackupAgents::withoutGlobalScopes()->find($job->s3_backup_agent_id);

        static::dispatch($agent->uuid, 'resume_job', ['job_uuid' => $job->uuid]);
    }

    public static function cancelJob(BackupJobs $job): void
    {
        $agent = BackupAgents::withoutGlobalScopes()->find($job->s3_backup_agent_id);

        static::dispatch($agent->uuid, 'cancel_job', ['job_uuid' => $job->uuid]);
    }

    /**
     * Ask the agent to restore the given Kopia snapshot to a temp location and
     * checksum-verify it — the direct answer to "restores silently fail"
     * from the design research. Longer timeout: a restore+checksum pass can
     * take much longer than a config command.
     */
    public static function verifySnapshot(BackupJobs $job, string $kopiaSnapshotId): void
    {
        $agent = BackupAgents::withoutGlobalScopes()->find($job->s3_backup_agent_id);

        static::dispatch($agent->uuid, 'verify_snapshot', [
            'job_uuid'    => $job->uuid,
            'snapshot_id' => $kopiaSnapshotId,
        ], timeoutS: 1800);
    }

    /**
     * Restore backup data (whole job or specific paths) to a real destination
     * on the agent's machine, then mandatorily checksum-verify it — the direct,
     * customer-facing answer to "restores silently fail" (see verifySnapshot()
     * above, which only ever restores to a throwaway temp path). Works for
     * both engines: for engine=kopia, $run picks the snapshot to extract from;
     * for engine=rsync, $run must be null — it always restores current bucket
     * state. See RestoreJobsService::startRestore() for the validation of
     * that pairing.
     */
    public static function restoreSnapshot(
        BackupJobs $job,
        ?BackupJobRuns $run,
        string $destinationPath,
        array $restorePaths = []
    ): RestoreJobs {
        $agent = BackupAgents::withoutGlobalScopes()->find($job->s3_backup_agent_id);

        $restore = RestoreJobsService::startRestore($job, $run, $destinationPath, $restorePaths, triggeredBy: 'manual');

        static::dispatch($agent->uuid, 'restore_snapshot', [
            'job_uuid'         => $job->uuid,
            'snapshot_id'      => $run?->kopia_snapshot_id, // null for rsync
            'destination_path' => $destinationPath,
            'restore_paths'    => $restorePaths, // empty = restore everything
            'restore_uuid'     => $restore->uuid,
        ], timeoutS: 3600); // longer than verify_snapshot's 1800s — real restore + checksum

        return $restore;
    }

    /**
     * Ask the agent to shut down cleanly. BackupAgentsService::revoke() sends
     * this before clearing agent_api_key — once the key is gone the agent
     * can no longer receive anything on its .cmd subject.
     */
    public static function revoke(string $agentUuid, string $reason = ''): void
    {
        static::dispatch($agentUuid, 'revoke', ['reason' => $reason]);
    }

    private static function dispatch(string $agentUuid, string $operation, array $params, int $timeoutS = 300): void
    {
        $agent = BackupAgents::withoutGlobalScopes()->where('uuid', $agentUuid)->firstOrFail();

        Log::info('[BackupAgentCommandService] Dispatching command', [
            'agent_uuid' => $agentUuid,
            'operation'  => $operation,
        ]);

        $agent->sendAgentCommand($operation, $params, $timeoutS);
    }
}
