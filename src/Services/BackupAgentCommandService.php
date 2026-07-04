<?php

namespace NextDeveloper\S3\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use NextDeveloper\Events\Services\NatsService;
use NextDeveloper\S3\Database\Models\BackupAgents;
use NextDeveloper\S3\Database\Models\BackupJobRuns;
use NextDeveloper\S3\Database\Models\BackupJobs;

/**
 * Sends commands to a backup.agent over NATS.
 *
 * Subject: agent.backup.{uuid}.cmd — envelope/operations documented in
 * docs/backup.agent/protocol.md.
 *
 * Deliberately fire-and-forget, like S3AgentCommandService — NOT the generic
 * NextDeveloper\Events\Services\AgentCommandsService::dispatch(), which
 * requires UserHelper::currentAccount()/me() (an authenticated HTTP request).
 * Several call sites here run from queued jobs with no request context at all
 * (e.g. BackupJobsService::syncAgent() after a job change, or a future
 * heartbeat-triggered full_sync), so this mirrors the same tradeoff the S3
 * agent already made for the same reason.
 */
class BackupAgentCommandService
{
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
        $commandId = (string) Str::uuid();
        $subject   = "agent.backup.{$agentUuid}.cmd";

        $envelope = [
            'v'          => 1,
            'id'         => $commandId,
            'type'       => 'command',
            'agent_type' => 'backup',
            'agent_uuid' => $agentUuid,
            'timestamp'  => time(),
            'payload'    => [
                'operation' => $operation,
                'params'    => empty($params) ? (object) [] : $params,
                'timeout_s' => $timeoutS,
            ],
        ];

        Log::info('[BackupAgentCommandService] Dispatching command', [
            'subject'    => $subject,
            'command_id' => $commandId,
            'operation'  => $operation,
        ]);

        app(NatsService::class)->publish($subject, $envelope);
    }
}
