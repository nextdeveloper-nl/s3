<?php

namespace NextDeveloper\S3\Services;

use Illuminate\Support\Facades\Log;
use NextDeveloper\Events\Services\Events;
use NextDeveloper\S3\Database\Models\Servers;

/**
 * Handles all inbound NATS messages from the storaged (seaweed) agent.
 *
 * Subscribed subject: agent.s3.*.evt
 * Envelope format: see docs/agent/seaweed-nats-contract.md §C
 */
class S3AgentService
{
    public static function handle(array $envelope): void
    {
        $type      = $envelope['type']       ?? null;
        $agentUuid = $envelope['agent_uuid'] ?? null;
        $payload   = $envelope['payload']    ?? [];

        if (!$agentUuid) {
            Log::warning('[S3AgentService] Missing agent_uuid', ['envelope' => $envelope]);
            return;
        }

        $server = Servers::withoutGlobalScopes()->where('uuid', $agentUuid)->first();

        if (!$server) {
            Log::warning('[S3AgentService] Unknown agent UUID', ['agent_uuid' => $agentUuid]);
            return;
        }

        match ($type) {
            'heartbeat' => static::handleHeartbeat($server, $payload),
            'telemetry' => static::handleTelemetry($server, $payload),
            'alert'     => static::handleAlert($server, $payload),
            'result'    => static::handleResult($server, $payload),
            default     => Log::warning('[S3AgentService] Unknown message type', [
                'type'       => $type,
                'agent_uuid' => $agentUuid,
            ]),
        };
    }

    // -------------------------------------------------------------------------

    /**
     * Keep the agent_status alive and trigger a full_sync when a previously
     * pending server comes online for the first time.
     */
    private static function handleHeartbeat(Servers $server, array $payload): void
    {
        $wasPending = $server->agent_status === 'pending';

        $server->update([
            'agent_status'       => 'connected',
            'agent_last_seen_at' => now(),
            'agent_version'      => $payload['version'] ?? $server->agent_version,
        ]);

        // First heartbeat from a newly-provisioned server: send the full desired state.
        if ($wasPending) {
            Log::info('[S3AgentService] Pending server connected — dispatching full_sync', [
                'server_uuid' => $server->uuid,
            ]);
            S3AgentCommandService::fullSync($server->uuid);
        }
    }

    /**
     * Persist a 30-second snapshot and update the live health fields on the server record.
     *
     * The NATS telemetry payload nests SeaweedFS stats under a "seaweedfs" key; both
     * ServerTelemetriesService and ServersService::updateHealthFromTelemetry() expect
     * that flat sub-object.
     */
    private static function handleTelemetry(Servers $server, array $payload): void
    {
        $seaweedfs = $payload['seaweedfs'] ?? [];

        if (empty($seaweedfs)) {
            Log::warning('[S3AgentService] Telemetry missing seaweedfs block', [
                'server_uuid' => $server->uuid,
            ]);
            return;
        }

        // Merge in agent_version from the top-level payload so updateHealthFromTelemetry
        // can persist it alongside the health fields.
        $seaweedfs['agent_version'] = $payload['uptime_s'] ?? null
            ? ($server->agent_version ?? null)
            : null;

        // Append-only snapshot row + update live server health
        ServerTelemetriesService::ingest($server->uuid, $seaweedfs);

        // Update the last-seen timestamp regardless of threshold PATCH logic
        $server->update([
            'agent_status'       => 'connected',
            'agent_last_seen_at' => now(),
        ]);
    }

    /**
     * Fire a platform event so existing alert handlers can react (PagerDuty, email, etc.).
     */
    private static function handleAlert(Servers $server, array $payload): void
    {
        $code = $payload['code'] ?? 'UNKNOWN';

        Log::warning('[S3AgentService] Agent alert', [
            'server_uuid' => $server->uuid,
            'severity'    => $payload['severity'] ?? null,
            'code'        => $code,
            'message'     => $payload['message'] ?? null,
        ]);

        Events::fire("alert:s3.{$code}", $server, $payload);
    }

    /**
     * Mark the corresponding command record as completed or failed.
     */
    private static function handleResult(Servers $server, array $payload): void
    {
        $commandId = $payload['command_id'] ?? null;
        $status    = $payload['status']     ?? 'unknown';

        if (!$commandId) {
            Log::warning('[S3AgentService] Result missing command_id', [
                'server_uuid' => $server->uuid,
            ]);
            return;
        }

        Log::info('[S3AgentService] Command result received', [
            'server_uuid' => $server->uuid,
            'command_id'  => $commandId,
            'status'      => $status,
            'message'     => $payload['message'] ?? null,
        ]);

        if ($status === 'failed') {
            Events::fire('agent.s3.command.failed', $server, $payload);
        }
    }
}
