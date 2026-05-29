<?php

namespace NextDeveloper\S3\Services;

use Illuminate\Support\Str;
use NextDeveloper\S3\Services\AbstractServices\AbstractServersService;

/**
 * This class is responsible from managing the data for Servers
 *
 * Class ServersService.
 *
 * @package NextDeveloper\S3\Database\Models
 */
class ServersService extends AbstractServersService
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * Register a new storage server and generate its initial API key.
     */
    public static function create(array $data)
    {
        $data['agent_api_key'] = Str::random(64);
        $data['agent_status']  = 'pending';

        return parent::create($data);
    }

    /**
     * Rotate the agent API key for a server (e.g. after a security incident).
     */
    public static function regenerateApiKey(string $uuid): \NextDeveloper\S3\Database\Models\Servers
    {
        $model = \NextDeveloper\S3\Database\Models\Servers::where('uuid', $uuid)->firstOrFail();
        $model->update(['agent_api_key' => Str::random(64)]);

        return $model->fresh();
    }

    /**
     * Update server health fields from a storaged telemetry payload.
     *
     * @param  string  $uuid
     * @param  array   $telemetry  Fields: master_reachable, volumes_degraded, capacity_pct, agent_version, etc.
     */
    public static function updateHealthFromTelemetry(string $uuid, array $telemetry): void
    {
        $model = \NextDeveloper\S3\Database\Models\Servers::where('uuid', $uuid)->first();
        if (!$model) {
            return;
        }

        $capacityPct = isset($telemetry['capacity_bytes_total']) && $telemetry['capacity_bytes_total'] > 0
            ? ($telemetry['capacity_bytes_used'] / $telemetry['capacity_bytes_total']) * 100
            : 0;

        $health = 'healthy';
        if (($telemetry['volumes_degraded'] ?? 0) > 0) {
            $health = 'degraded';
        }
        if (!($telemetry['master_reachable'] ?? true)) {
            $health = 'unreachable';
        }
        if ($capacityPct >= 90) {
            $health = 'critical';
        } elseif ($capacityPct >= 80 && $health === 'healthy') {
            $health = 'warning';
        }

        $update = [
            'health'             => $health,
            'agent_last_seen_at' => now(),
            'agent_status'       => 'connected',
        ];

        if (!empty($telemetry['agent_version'])) {
            $update['agent_version'] = $telemetry['agent_version'];
        }
        if (!empty($telemetry['seaweedfs_version'])) {
            $update['seaweedfs_version'] = $telemetry['seaweedfs_version'];
        }

        // Store component health as JSONB
        $components = $model->components ?? [];
        foreach (['master', 'volume', 'filer', 's3'] as $component) {
            if (array_key_exists("{$component}_reachable", $telemetry)) {
                $components[$component] = ['reachable' => $telemetry["{$component}_reachable"]];
            }
        }
        if (!empty($components)) {
            $update['components'] = $components;
        }

        $model->update($update);
    }

    /**
     * Mark a server as disconnected when its SSE stream closes.
     */
    public static function markDisconnected(string $uuid): void
    {
        $model = \NextDeveloper\S3\Database\Models\Servers::where('uuid', $uuid)->first();
        if ($model) {
            $model->update(['agent_status' => 'disconnected']);
        }
    }
}