<?php

namespace NextDeveloper\S3\Jobs\Nats;

use Illuminate\Support\Facades\Log;
use NextDeveloper\Events\Database\Models\AgentCommands;
use NextDeveloper\Events\Jobs\AbstractAgentEventJob;
use NextDeveloper\Events\Services\Events;
use NextDeveloper\S3\Database\Models\BackupAgents;
use NextDeveloper\S3\Database\Models\BackupJobRuns;
use NextDeveloper\S3\Database\Models\BackupJobs;
use NextDeveloper\S3\Services\BackupAgentCommandService;
use NextDeveloper\S3\Services\BackupJobRunsService;
use NextDeveloper\S3\Services\RestoreJobsService;

/**
 * Dispatched by NatsListenCommand for every message received on agent.backup.*.evt.
 * Replaces the old BackupAgentEventService::handle() dispatched via the main
 * app's App\Jobs\Nats\HandleBackupAgentEventJob.
 *
 * Protocol: see docs/backup.agent/protocol.md
 */
class HandleBackupAgentEventJob extends AbstractAgentEventJob
{
    // Same dedicated queue as HandleS3AgentEventJob — keeps NATS agent-event
    // ingestion off the shared default queue.
    public $queue = 's3-agent-events';

    protected function resolveAgentModel(string $agentUuid)
    {
        return BackupAgents::withoutGlobalScopes()->where('uuid', $agentUuid)->first();
    }

    /**
     * Keep the agent alive and trigger a full_sync the first time a freshly
     * registered agent's heartbeat arrives — same "pending agent connected"
     * pattern the S3 agent uses for storaged.
     */
    protected function updateHeartbeat($model, array $payload): void
    {
        $wasNeverSeen = $model->last_seen_at === null;

        $model->update([
            'health'        => 'healthy',
            'last_seen_at'  => now(),
            'agent_version' => $payload['version'] ?? $model->agent_version,
        ]);

        if ($wasNeverSeen) {
            Log::info('[HandleBackupAgentEventJob] Agent connected for the first time — dispatching full_sync', [
                'agent_uuid' => $model->uuid,
            ]);
            BackupAgentCommandService::fullSync($model->uuid);
        }
    }

    protected function handleDomainEvent(string $type, $model, array $payload): void
    {
        match ($type) {
            'telemetry' => $this->handleTelemetry($model),
            'job_run'   => $this->handleJobRun($model, $payload),
            'alert'     => $this->handleAlert($model, $payload),
            default     => Log::warning('[HandleBackupAgentEventJob] Unknown message type', [
                'type'       => $type,
                'agent_uuid' => $model->uuid,
            ]),
        };
    }

    /**
     * Response to a command dispatched by BackupAgentCommandService — the
     * only path with a command_id, since run_job_now/pause_job/etc. are all
     * platform-initiated. Scheduled runs never hit this method (see handleJobRun).
     */
    protected function onCommandResult($model, ?AgentCommands $command, array $payload): void
    {
        $agent = $model;

        if (!$agent) {
            return;
        }

        $commandId = $command->uuid ?? ($payload['command_id'] ?? null);
        $status    = $payload['status'] ?? 'unknown';
        $output = $payload['output'] ?? [];

        $agent->update(['health' => 'healthy', 'last_seen_at' => now()]);

        // run_job_now results carry the run_uuid we generated when the command was sent.
        $runUuid = $output['run_uuid'] ?? null;

        if ($runUuid) {
            if ($status === 'completed') {
                BackupJobRunsService::completeRun($runUuid, $output);
            } elseif (in_array($status, ['failed', 'rejected'], true)) {
                BackupJobRunsService::failRun($runUuid, $payload['message'] ?? null);
            }
        }

        // restore_snapshot results carry the restore_uuid we generated when the
        // command was sent. A restore only "completes" if the agent finished
        // AND its own checksum verification passed — anything else is a
        // failure, not a success with a caveat (see RestoreJobsService).
        $restoreUuid = $output['restore_uuid'] ?? null;

        if ($restoreUuid) {
            if ($status === 'completed' && ($output['verified'] ?? false)) {
                RestoreJobsService::completeRestore($restoreUuid, $output);
            } else {
                RestoreJobsService::failRestore($restoreUuid, $payload['message'] ?? 'Checksum verification failed');
            }
        }

        if ($status === 'failed') {
            Events::fire('agent.backup.command.failed', $agent, $payload);
        }

        // A `rejected` command (e.g. run_job_now for a job_uuid the agent has
        // never seen — it reconnected before the last full_sync landed, or a
        // job was created/changed and the agent's copy is stale) means the
        // agent's job list is out of sync with the platform. Re-sending
        // full_sync is idempotent and gets the agent caught up so the next
        // attempt succeeds. For run_job_now specifically, also retry the run
        // itself (bounded — see BackupAgentCommandService::retryRunJobNowAfterRejection())
        // rather than just resyncing and leaving it at that.
        if ($status === 'rejected') {
            Log::warning('[HandleBackupAgentEventJob] Command rejected — re-syncing agent job list', [
                'agent_uuid' => $agent->uuid,
                'command_id' => $commandId,
                'message'    => $payload['message'] ?? null,
            ]);

            BackupAgentCommandService::fullSync($agent->uuid);

            if ($runUuid) {
                $run = BackupJobRuns::withoutGlobalScopes()->where('uuid', $runUuid)->first();
                $job = $run ? BackupJobs::withoutGlobalScopes()->find($run->s3_backup_job_id) : null;

                if ($job) {
                    BackupAgentCommandService::retryRunJobNowAfterRejection($job);
                }
            }
        }

        Log::info('[HandleBackupAgentEventJob] Command result received', [
            'agent_uuid' => $agent->uuid,
            'command_id' => $commandId,
            'status'     => $status,
        ]);
    }

    // -------------------------------------------------------------------------

    /**
     * OS-level metrics (disk free, etc). There is no dedicated telemetry
     * table for backup agents — job outcomes (the thing that actually
     * matters here) arrive via `job_run`/`result` instead.
     */
    private function handleTelemetry(BackupAgents $agent): void
    {
        $agent->update([
            'health'       => 'healthy',
            'last_seen_at' => now(),
        ]);
    }

    /**
     * The agent's own cron fired a job and it already finished — no command
     * was ever sent for this, so there's no `result`/command_id to match.
     * See BackupJobRunsService::recordRun() for why the run row is created
     * (not updated) here, keyed by the agent's own run uuid.
     */
    private function handleJobRun(BackupAgents $agent, array $payload): void
    {
        $jobUuid = $payload['job_uuid'] ?? null;

        if (!$jobUuid) {
            Log::warning('[HandleBackupAgentEventJob] job_run missing job_uuid', ['agent_uuid' => $agent->uuid]);
            return;
        }

        $job = BackupJobs::withoutGlobalScopes()->where('uuid', $jobUuid)->first();

        if (!$job) {
            Log::warning('[HandleBackupAgentEventJob] job_run for unknown job', [
                'agent_uuid' => $agent->uuid,
                'job_uuid'   => $jobUuid,
            ]);
            return;
        }

        BackupJobRunsService::recordRun($job, $payload);

        $agent->update(['health' => 'healthy', 'last_seen_at' => now()]);

        Log::info('[HandleBackupAgentEventJob] Job run recorded', [
            'agent_uuid' => $agent->uuid,
            'job_uuid'   => $jobUuid,
            'status'     => $payload['status'] ?? 'unknown',
        ]);
    }

    /**
     * Fire a platform event so existing alert handlers can react.
     */
    private function handleAlert(BackupAgents $agent, array $payload): void
    {
        $code = $payload['code'] ?? 'UNKNOWN';

        Log::warning('[HandleBackupAgentEventJob] Agent alert', [
            'agent_uuid' => $agent->uuid,
            'severity'   => $payload['severity'] ?? null,
            'code'       => $code,
            'message'    => $payload['message'] ?? null,
        ]);

        Events::fire("alert:backup.{$code}", $agent, $payload);
    }
}
