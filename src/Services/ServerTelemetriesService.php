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
     * @param  string  $serverUuid  UUID of the s3_servers record
     * @param  array   $telemetry   Keys: master_reachable, volume_count, volumes_degraded,
     *                              capacity_bytes_total, capacity_bytes_used, agent_version, etc.
     */
    public static function ingest(string $serverUuid, array $telemetry): void
    {
        $server = Servers::where('uuid', $serverUuid)->first();
        if (!$server) {
            return;
        }

        $capacityPct = isset($telemetry['capacity_bytes_total']) && $telemetry['capacity_bytes_total'] > 0
            ? round(($telemetry['capacity_bytes_used'] / $telemetry['capacity_bytes_total']) * 100, 2)
            : 0;

        \NextDeveloper\S3\Database\Models\ServerTelemetries::create([
            's3_server_id'          => $server->id,
            'reported_at'           => now(),
            'master_reachable'      => $telemetry['master_reachable'] ?? true,
            'volume_count'          => $telemetry['volume_count'] ?? 0,
            'volumes_degraded'      => $telemetry['volumes_degraded'] ?? 0,
            'capacity_bytes_total'  => $telemetry['capacity_bytes_total'] ?? 0,
            'capacity_bytes_used'   => $telemetry['capacity_bytes_used'] ?? 0,
            'capacity_pct'          => $capacityPct,
        ]);

        // Update live server health from this snapshot
        ServersService::updateHealthFromTelemetry($serverUuid, $telemetry);
    }
}