<?php

namespace NextDeveloper\S3\Services;

use Illuminate\Support\Facades\Log;
use NextDeveloper\Events\Services\Events;
use NextDeveloper\IAM\Helpers\UserHelper;
use NextDeveloper\S3\Database\Models\BackupAgents;
use NextDeveloper\S3\Database\Models\BackupJobs;

/**
 * Handles all inbound NATS messages from a backup.agent.
 *
 * Subscribed subject: agent.backup.*.evt
 * Envelope format: see docs/backup.agent/protocol.md
 */
class BackupAgentEventService
{
    public static function handle(array $envelope): void
    {
        $type      = $envelope['type']      ?? null;
        $agentUuid = $envelope['agent_uuid'] ?? null;
        $payload   = $envelope['payload']   ?? [];

        if (!$agentUuid) {
            Log::warning('[BackupAgentEventService] Missing agent_uuid', ['envelope' => $envelope]);
            return;
        }

        $agent = BackupAgents::withoutGlobalScopes()->where('uuid', $agentUuid)->first();

        if (!$agent) {
            Log::warning('[BackupAgentEventService] Unknown agent UUID', ['agent_uuid' => $agentUuid]);
            return;
        }

        match ($type) {
            'heartbeat' => static::handleHeartbeat($agent, $payload),
            'telemetry' => static::handleTelemetry($agent, $payload),
            'job_run'   => static::handleJobRun($agent, $payload),
            'alert'     => static::handleAlert($agent, $payload),
            'result'    => static::handleResult($agent, $payload),
            default     => Log::warning('[BackupAgentEventService] Unknown message type', [
                'type'       => $type,
                'agent_uuid' => $agentUuid,
            ]),
        };
    }

    // -------------------------------------------------------------------------

    /**
     * Keep the agent alive and trigger a full_sync the first time a freshly
     * registered agent's heartbeat arrives — same "pending agent connected"
     * pattern S3AgentService uses for storaged.
     */
    private static function handleHeartbeat(BackupAgents $agent, array $payload): void
    {
        $wasNeverSeen = $agent->last_seen_at === null;

        UserHelper::runAsAdmin(function () use ($agent, $payload) {
            $agent->update([
                'health'        => 'healthy',
                'last_seen_at'  => now(),
                'agent_version' => $payload['version'] ?? $agent->agent_version,
            ]);
        });

        if ($wasNeverSeen) {
            Log::info('[BackupAgentEventService] Agent connected for the first time — dispatching full_sync', [
                'agent_uuid' => $agent->uuid,
            ]);
            BackupAgentCommandService::fullSync($agent->uuid);
        }
    }

    /**
     * OS-level metrics (disk free, etc). There is no dedicated telemetry
     * table for backup agents — job outcomes (the thing that actually
     * matters here) arrive via `job_run`/`result` instead.
     */
    private static function handleTelemetry(BackupAgents $agent, array $payload): void
    {
        UserHelper::runAsAdmin(function () use ($agent) {
            $agent->update([
                'health'       => 'healthy',
                'last_seen_at' => now(),
            ]);
        });
    }

    /**
     * The agent's own cron fired a job and it already finished — no command
     * was ever sent for this, so there's no `result`/command_id to match.
     * See BackupJobRunsService::recordRun() for why the run row is created
     * (not updated) here, keyed by the agent's own run uuid.
     */
    private static function handleJobRun(BackupAgents $agent, array $payload): void
    {
        $jobUuid = $payload['job_uuid'] ?? null;

        if (!$jobUuid) {
            Log::warning('[BackupAgentEventService] job_run missing job_uuid', ['agent_uuid' => $agent->uuid]);
            return;
        }

        $job = BackupJobs::withoutGlobalScopes()->where('uuid', $jobUuid)->first();

        if (!$job) {
            Log::warning('[BackupAgentEventService] job_run for unknown job', [
                'agent_uuid' => $agent->uuid,
                'job_uuid'   => $jobUuid,
            ]);
            return;
        }

        UserHelper::runAsAdmin(function () use ($agent, $job, $payload) {
            BackupJobRunsService::recordRun($job, $payload);

            $agent->update(['health' => 'healthy', 'last_seen_at' => now()]);
        });

        Log::info('[BackupAgentEventService] Job run recorded', [
            'agent_uuid' => $agent->uuid,
            'job_uuid'   => $jobUuid,
            'status'     => $payload['status'] ?? 'unknown',
        ]);
    }

    /**
     * Fire a platform event so existing alert handlers can react.
     */
    private static function handleAlert(BackupAgents $agent, array $payload): void
    {
        $code = $payload['code'] ?? 'UNKNOWN';

        Log::warning('[BackupAgentEventService] Agent alert', [
            'agent_uuid' => $agent->uuid,
            'severity'   => $payload['severity'] ?? null,
            'code'       => $code,
            'message'    => $payload['message'] ?? null,
        ]);

        Events::fire("alert:backup.{$code}", $agent, $payload);
    }

    /**
     * Response to a command dispatched by BackupAgentCommandService — the
     * only path with a command_id, since run_job_now/pause_job/etc. are all
     * platform-initiated. Scheduled runs never hit this method (see handleJobRun).
     */
    private static function handleResult(BackupAgents $agent, array $payload): void
    {
        $commandId = $payload['command_id'] ?? null;
        $status    = $payload['status']     ?? 'unknown';
        $output    = $payload['output']     ?? [];

        if (!$commandId) {
            Log::warning('[BackupAgentEventService] Result missing command_id', ['agent_uuid' => $agent->uuid]);
            return;
        }

        UserHelper::runAsAdmin(function () use ($agent) {
            $agent->update(['health' => 'healthy', 'last_seen_at' => now()]);
        });

        // run_job_now results carry the run_uuid we generated when the command was sent.
        $runUuid = $output['run_uuid'] ?? null;

        if ($runUuid) {
            if ($status === 'completed') {
                BackupJobRunsService::completeRun($runUuid, $output);
            } elseif (in_array($status, ['failed', 'rejected'], true)) {
                BackupJobRunsService::failRun($runUuid, $payload['message'] ?? null);
            }
        }

        if ($status === 'failed') {
            Events::fire('agent.backup.command.failed', $agent, $payload);
        }

        Log::info('[BackupAgentEventService] Command result received', [
            'agent_uuid' => $agent->uuid,
            'command_id' => $commandId,
            'status'     => $status,
        ]);
    }
}
