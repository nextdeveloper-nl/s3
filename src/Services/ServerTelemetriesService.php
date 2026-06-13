<?php

namespace NextDeveloper\S3\Services;

use NextDeveloper\S3\Database\Models\Servers;
use NextDeveloper\S3\Services\AbstractServices\AbstractServerTelemetriesService;

/**
 * This class is responsible from managing the data for ServerTelemetries
 *
 * Class ServerTelemetriesService.
 *
 * @package NextDeveloper\S3\Database\Models
 */
class ServerTelemetriesService extends AbstractServerTelemetriesService
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * Persist a 30-second telemetry snapshot from the storaged agent
     * and update the parent server's health summary.
     *
     * The agent payload contains OS-level metrics (cpu, memory, disks, network) in all versions.
     * SeaweedFS cluster metrics (master_reachable, volume_count, etc.) arrive under a "seaweedfs"
     * sub-key in future agent versions.
     *
     * @param  string  $serverUuid  UUID of the s3_servers record
     * @param  array   $payload     Full telemetry payload from the NATS envelope
     */
    public static function ingest(string $serverUuid, array $payload): void
    {
        $server = Servers::withoutGlobalScopes()->where('uuid', $serverUuid)->first();
        if (!$server) {
            return;
        }

        // SeaweedFS cluster metrics — present when the agent sends a "seaweedfs" block
        $seaweedfs   = $payload['seaweedfs'] ?? [];
        $capacityPct = isset($seaweedfs['capacity_bytes_total']) && $seaweedfs['capacity_bytes_total'] > 0
            ? round(($seaweedfs['capacity_bytes_used'] / $seaweedfs['capacity_bytes_total']) * 100, 2)
            : null;

        \NextDeveloper\S3\Database\Models\ServerTelemetries::create([
            's3_server_id'         => $server->id,
            'reported_at'          => now(),
            // OS-level metrics — always present in current agent version
            'cpu'     => $payload['cpu']     ?? null,
            'ram'     => $payload['memory']  ?? null,
            'disk'    => $payload['disks']   ?? null,
            'network' => $payload['network'] ?? null,
            // SeaweedFS cluster health — present in future agent versions
            'master_reachable'     => $seaweedfs['master_reachable']    ?? null,
            'volume_count'         => $seaweedfs['volume_count']         ?? null,
            'volumes_degraded'     => $seaweedfs['volumes_degraded']     ?? null,
            'capacity_bytes_total' => $seaweedfs['capacity_bytes_total'] ?? null,
            'capacity_bytes_used'  => $seaweedfs['capacity_bytes_used']  ?? null,
            'capacity_pct'         => $capacityPct,
        ]);

        // Update live server health from the SeaweedFS block (no-op when empty)
        if (!empty($seaweedfs)) {
            ServersService::updateHealthFromTelemetry($serverUuid, $seaweedfs);
        }
    }
}