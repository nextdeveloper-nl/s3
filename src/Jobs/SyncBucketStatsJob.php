<?php

namespace NextDeveloper\S3\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use NextDeveloper\S3\Database\Models\Servers;
use NextDeveloper\S3\Services\S3AgentCommandService;

/**
 * Dispatches an s3.bucket.stats pull command to every connected S3 server.
 *
 * Runs every minute via the scheduler. The agent responds asynchronously;
 * the result is processed by HandleS3AgentEventJob → S3AgentService::handleResult()
 * which calls updateBucketStatsFromTelemetry() to update the s3_buckets table.
 */
class SyncBucketStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    public function handle(): void
    {
        $servers = Servers::withoutGlobalScopes()
            ->where('agent_status', 'connected')
            ->whereNull('deleted_at')
            ->get();

        foreach ($servers as $server) {
            try {
                // Fire-and-forget: agent replies on agent.s3.{uuid}.evt and the
                // NATS listener pipeline persists the stats from the result.
                S3AgentCommandService::bucketStats($server->uuid);

                Log::debug('[SyncBucketStatsJob] s3.bucket.stats dispatched', [
                    'server_uuid' => $server->uuid,
                ]);
            } catch (\Throwable $e) {
                Log::warning('[SyncBucketStatsJob] Failed to dispatch for server', [
                    'server_uuid' => $server->uuid,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        Log::info('[SyncBucketStatsJob] Dispatched bucket stats pull', [
            'server_count' => $servers->count(),
        ]);
    }
}
