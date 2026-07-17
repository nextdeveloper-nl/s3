<?php

namespace NextDeveloper\S3\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use NextDeveloper\Events\Services\Events;
use NextDeveloper\IAM\Helpers\UserHelper;
use NextDeveloper\S3\Database\Models\AccessKeys;
use NextDeveloper\S3\Database\Models\Accounts;
use NextDeveloper\S3\Database\Models\Buckets;
use NextDeveloper\S3\Database\Models\Servers;
use NextDeveloper\S3\Helpers\WormHelper;
use NextDeveloper\S3\Services\AuditLogsService;
use NextDeveloper\S3\Services\BandwidthMonthliesService;
use NextDeveloper\S3\Services\DepositLedgersService;
use NextDeveloper\S3\Services\S3AgentCommandService;
use NextDeveloper\S3\Services\WormCommitmentsService;

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

        $wasPending = $server->agent_status === 'pending';

        // Any envelope — telemetry, alert, audit, result, anything — proves the agent
        // is alive. Refresh the heartbeat unconditionally here rather than in each
        // handler, so agent_last_seen_at doesn't go stale just because a given tick
        // only produced alert/audit traffic instead of a telemetry message.
        UserHelper::runAsAdmin(function () use ($server) {
            $server->update([
                'agent_status'       => 'connected',
                'agent_last_seen_at' => now(),
            ]);
        });

        match ($type) {
            'heartbeat'    => static::handleHeartbeat($server, $payload, $wasPending),
            'telemetry'    => static::handleTelemetry($server, $payload),
            's3_telemetry' => static::handleS3Telemetry($server, $payload),
            's3_audit'     => static::handleS3Audit($server, $payload),
            'alert'        => static::handleAlert($server, $payload),
            'result'       => static::handleResult($server, $payload),
            default        => Log::warning('[S3AgentService] Unknown message type', [
                'type'       => $type,
                'agent_uuid' => $agentUuid,
            ]),
        };
    }

    // -------------------------------------------------------------------------

    /**
     * Record the agent version and trigger a full_sync when a previously
     * pending server comes online for the first time. agent_status/agent_last_seen_at
     * are already refreshed unconditionally in handle().
     */
    private static function handleHeartbeat(Servers $server, array $payload, bool $wasPending): void
    {
        if (!empty($payload['version']) && $payload['version'] !== $server->agent_version) {
            UserHelper::runAsAdmin(function () use ($server, $payload) {
                $server->update(['agent_version' => $payload['version']]);
            });
        }

        // First heartbeat from a newly-provisioned server: send the full desired state.
        if ($wasPending) {
            Log::info('[S3AgentService] Pending server connected — dispatching full_sync', [
                'server_uuid' => $server->uuid,
            ]);
            S3AgentCommandService::fullSync($server->uuid);
        }
    }

    /**
     * Handles type=s3_telemetry — the S3-specific 30s tick from the agent.
     *
     * Payload keys: components (SeaweedFS service health), buckets (storage stats),
     * traffic (per-bucket request/byte deltas since last flush).
     */
    private static function handleS3Telemetry(Servers $server, array $payload): void
    {
        // Store raw component health from the agent (master, volume, filer, s3, etc.).
        // agent_status/agent_last_seen_at are already refreshed unconditionally in handle().
        if (!empty($payload['components'])) {
            UserHelper::runAsAdmin(function () use ($server, $payload) {
                $server->update(['components' => $payload['components']]);
            });
        }

        // Update per-bucket storage stats (object_count, size_bytes, replica_health)
        static::updateBucketStatsFromTelemetry($server, $payload['buckets'] ?? []);

        // Accumulate per-bucket traffic deltas into the monthly bandwidth table
        static::handleTrafficDeltas($server, $payload['traffic'] ?? []);
    }

    /**
     * Persist a 30-second snapshot and update the live health fields on the server record.
     *
     * The agent sends OS-level metrics (cpu, memory, disks, network) as type=telemetry.
     */
    private static function handleTelemetry(Servers $server, array $payload): void
    {
        // agent_status/agent_last_seen_at are already refreshed unconditionally in handle().

        // Persist the snapshot — stores OS metrics now, SeaweedFS fields when available.
        ServerTelemetriesService::ingest($server->uuid, $payload);

        // Per-bucket stats — present when agent sends a "buckets" array.
        static::updateBucketStatsFromTelemetry($server, $payload['buckets'] ?? []);

        // Per-bucket traffic deltas — each item is bytes since the last 30s flush.
        static::handleTrafficDeltas($server, $payload['traffic'] ?? []);
    }

    /**
     * Walk the buckets array from a telemetry payload and update each matching DB row.
     * Uses the bucket name + server as the lookup key.
     */
    private static function updateBucketStatsFromTelemetry(Servers $server, array $buckets): void
    {
        if (empty($buckets)) {
            return;
        }

        $touchedAccountIds = [];

        foreach ($buckets as $stats) {
            $name = $stats['name'] ?? null;
            if (!$name) {
                continue;
            }

            $bucket = Buckets::withoutGlobalScopes()
                ->where('s3_server_id', $server->id)
                ->where('bucket_name', $name)
                ->whereNull('deleted_at')
                ->first();

            if (!$bucket) {
                Log::debug('[S3AgentService] Telemetry bucket not found in DB — skipping', [
                    'server_uuid' => $server->uuid,
                    'bucket_name' => $name,
                ]);
                continue;
            }

            UserHelper::runAsAdmin(function () use ($bucket, $stats) {
                $update = [
                    'object_count'   => $stats['object_count']   ?? $bucket->object_count,
                    'size_bytes'     => $stats['size_bytes']      ?? $bucket->size_bytes,
                    'replica_health' => $stats['replica_health']  ?? $bucket->replica_health,
                ];

                // Mirror versioning_status from the agent for non-WORM buckets.
                // WORM buckets report "" because SeaweedFS manages versioning internally.
                if (!empty($stats['versioning_status']) && empty($bucket->object_lock_enabled)) {
                    $update['versioning'] = $stats['versioning_status'] === 'enabled'
                        ? 'Enabled'
                        : 'Suspended';
                }

                $bucket->update($update);

                // Keep the WORM commitment's quota_bytes in sync with the actual bucket size.
                if (!empty($bucket->object_lock_enabled) && isset($stats['size_bytes'])) {
                    $commitment = WormCommitmentsService::getActiveForBucket($bucket->id);
                    $commitment?->update(['quota_bytes' => $stats['size_bytes']]);
                }
            });

            $touchedAccountIds[$bucket->s3_account_id] = true;
        }

        foreach (array_keys($touchedAccountIds) as $accountId) {
            static::syncAccountStorageFromBuckets((int) $accountId);
        }

        Log::info('[S3AgentService] Bucket stats updated from telemetry', [
            'server_uuid'  => $server->uuid,
            'bucket_count' => count($buckets),
        ]);
    }

    /**
     * Re-sum an account's live buckets and write the totals back onto s3_accounts.
     *
     * storage_bytes_used/object_count on the account row are denormalised
     * counters read by QuotaHelper and UsageSnapshotsService — nothing else
     * keeps them in sync with the per-bucket figures telemetry just updated.
     */
    private static function syncAccountStorageFromBuckets(int $accountId): void
    {
        $totals = Buckets::withoutGlobalScopes()
            ->where('s3_account_id', $accountId)
            ->whereNull('deleted_at')
            ->selectRaw('coalesce(sum(size_bytes), 0) as storage_bytes, coalesce(sum(object_count), 0) as objects')
            ->first();

        UserHelper::runAsAdmin(function () use ($accountId, $totals) {
            Accounts::withoutGlobalScopes()
                ->where('id', $accountId)
                ->update([
                    'storage_bytes_used' => $totals->storage_bytes,
                    'object_count'       => $totals->objects,
                ]);
        });
    }

    /**
     * Accumulate per-bucket traffic deltas into the monthly bandwidth table.
     *
     * Each item: {bucket: string, bytes_in: int, bytes_out: int}
     * bytes_in  = ingress (PUT/POST request_length)
     * bytes_out = egress  (GET bytes_sent)
     * 4xx/5xx responses are excluded by the agent before reporting.
     */
    private static function handleTrafficDeltas(Servers $server, array $traffic): void
    {
        if (empty($traffic)) {
            return;
        }

        foreach ($traffic as $delta) {
            $bucketName = $delta['bucket'] ?? null;
            $bytesIn    = (int) ($delta['bytes_in']  ?? 0);
            $bytesOut   = (int) ($delta['bytes_out'] ?? 0);

            if (!$bucketName || ($bytesIn === 0 && $bytesOut === 0)) {
                continue;
            }

            $bucket = Buckets::withoutGlobalScopes()
                ->where('s3_server_id', $server->id)
                ->where('bucket_name', $bucketName)
                ->whereNull('deleted_at')
                ->first();

            if (!$bucket) {
                Log::debug('[S3AgentService] Traffic delta bucket not found — skipping', [
                    'server_uuid' => $server->uuid,
                    'bucket_name' => $bucketName,
                ]);
                continue;
            }

            UserHelper::runAsAdmin(function () use ($bucket, $bytesIn, $bytesOut) {
                if ($bytesIn > 0) {
                    BandwidthMonthliesService::addIngress($bucket->s3_account_id, $bytesIn);
                }
                if ($bytesOut > 0) {
                    BandwidthMonthliesService::addEgress($bucket->s3_account_id, $bytesOut);
                }
            });
        }

        Log::info('[S3AgentService] Traffic deltas accumulated', [
            'server_uuid'  => $server->uuid,
            'bucket_count' => count($traffic),
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
        $output    = $payload['output']     ?? [];

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

        if ($status === 'completed') {
            // Agent completed a command — it is reachable and healthy.
            // agent_status/agent_last_seen_at are already refreshed unconditionally in handle().
            UserHelper::runAsAdmin(function () use ($server) {
                $server->update(['health' => 'healthy']);
            });

            // full_sync results carry bucket/IAM diff counts in the output.
            if (array_key_exists('buckets_created', $output)) {
                Log::info('[S3AgentService] full_sync completed', [
                    'server_uuid'     => $server->uuid,
                    'buckets_created' => $output['buckets_created'] ?? 0,
                    'buckets_deleted' => $output['buckets_deleted'] ?? 0,
                    'iam_created'     => $output['iam_created']     ?? 0,
                    'iam_deleted'     => $output['iam_deleted']     ?? 0,
                ]);
            }

            // s3.bucket.stats results: output is {"buckets": [...], "traffic": [...]}
            if (isset($output['buckets']) && is_array($output['buckets'])) {
                static::updateBucketStatsFromTelemetry($server, $output['buckets']);
            }

            if (!empty($output['traffic'])) {
                static::handleTrafficDeltas($server, $output['traffic']);
            }
        } elseif ($status === 'failed') {
            Events::fire('agent.s3.command.failed', $server, $payload);
        }
    }

    /**
     * Receive per-object PUT/DELETE events (type=s3_audit) pushed by the agent
     * and write an immutable entry to s3_audit_logs for each.
     *
     * Payload shape (agent contract v1):
     * {
     *   "events": [
     *     {
     *       "bucket":       "my-bucket",
     *       "object_key":   "path/to/file.txt",
     *       "action":       "PUT" | "DELETE",  // POST normalised to PUT by agent
     *       "size_bytes":   580,               // request body size; 0 for DELETE
     *       "retain_until": "2026-07-13T..",   // WORM PUT only, omitted otherwise
     *       "access_key":   "pcsadmin9e09aeda",
     *       "client_ip":    "185.255.172.184",
     *       "performed_at": "2026-06-13T10:13:07Z"
     *     }
     *   ]
     * }
     * Multiple mutations in the same 500 ms window arrive as one envelope.
     */
    private static function handleS3Audit(Servers $server, array $payload): void
    {
        $events = $payload['events'] ?? [];
        if (empty($events)) {
            return;
        }

        // Cache bucket and access-key lookups within this batch to avoid N+1 queries.
        $bucketCache = [];
        $keyCache    = [];

        foreach ($events as $event) {
            $bucketName  = $event['bucket']       ?? null;
            $objectKey   = $event['object_key']   ?? null;
            $action      = strtoupper($event['action'] ?? '');
            $sizeBytes   = isset($event['size_bytes'])   ? (int) $event['size_bytes']   : null;
            $retainUntil = $event['retain_until'] ?? null;
            $accessKeyId = $event['access_key']   ?? null;
            $clientIp    = $event['client_ip']    ?? null;
            $performedAt = $event['performed_at'] ?? now();

            if (!$bucketName || !$objectKey || !in_array($action, ['PUT', 'DELETE'], true)) {
                continue;
            }

            // Resolve bucket
            if (!array_key_exists($bucketName, $bucketCache)) {
                $bucketCache[$bucketName] = Buckets::withoutGlobalScopes()
                    ->where('s3_server_id', $server->id)
                    ->where('bucket_name', $bucketName)
                    ->whereNull('deleted_at')
                    ->first();
            }

            $bucket = $bucketCache[$bucketName];
            if (!$bucket) {
                Log::debug('[S3AgentService] object_event bucket not found — skipping', [
                    'server_uuid' => $server->uuid,
                    'bucket_name' => $bucketName,
                ]);
                continue;
            }

            // Safety net: skip if audit was disabled after the last full_sync was sent.
            if (empty($bucket->is_object_audit_enabled) && empty($bucket->object_lock_enabled)) {
                continue;
            }

            // Resolve access key record (lookup by key string)
            $accessKey = null;
            if ($accessKeyId) {
                if (!array_key_exists($accessKeyId, $keyCache)) {
                    $keyCache[$accessKeyId] = AccessKeys::withoutGlobalScopes()
                        ->where('access_key', $accessKeyId)
                        ->first();
                }
                $accessKey = $keyCache[$accessKeyId];
            }

            // Resolve active WORM commitment when the bucket has object lock enabled.
            $commitment   = null;
            $commitmentId = null;
            if (!empty($bucket->object_lock_enabled)) {
                $commitment   = WormCommitmentsService::getActiveForBucket($bucket->id);
                $commitmentId = $commitment?->id;
            }

            UserHelper::runAsAdmin(function () use (
                $action, $accessKeyId, $bucket, $server, $accessKey,
                $commitment, $commitmentId, $performedAt, $objectKey, $sizeBytes, $retainUntil, $clientIp
            ) {
                AuditLogsService::log(
                    'object.' . strtolower($action),
                    $accessKeyId ?? 'unknown',
                    [
                        'iam_account_id'        => $bucket->iam_account_id,
                        's3_account_id'         => $bucket->s3_account_id,
                        's3_server_id'          => $server->id,
                        's3_bucket_id'          => $bucket->id,
                        's3_access_key_id'      => $accessKey?->id,
                        's3_worm_commitment_id' => $commitmentId,
                        'performed_at'          => $performedAt,
                        // object_key, size_bytes, retain_until, client_ip land in data JSON
                        'object_key'            => $objectKey,
                        'size_bytes'            => $sizeBytes,
                        'retain_until'          => $retainUntil,
                        'client_ip'             => $clientIp,
                    ]
                );

                // Charge incremental WORM deposit for every PUT: cost of storing this
                // file for the remaining lock period of the active commitment.
                if ($commitment && $action === 'PUT' && $sizeBytes > 0) {
                    $daysRemaining = max(1, (int) Carbon::now()->diffInDays(
                        Carbon::parse($commitment->locks_until), false
                    ));
                    $price   = (float) ($commitment->price_per_gb_mo
                        ?? config('s3.worm.price_per_gb_mo', 0.023));
                    $deposit = WormHelper::calculateDeposit($sizeBytes, $price, $daysRemaining);

                    if ($deposit > 0) {
                        DepositLedgersService::deposit(
                            $commitment->s3_account_id,
                            $commitment->id,
                            $deposit,
                            $daysRemaining,
                            $commitment->iam_account_id ?: null
                        );
                    }
                }
            });
        }

        Log::info('[S3AgentService] Object events logged', [
            'server_uuid' => $server->uuid,
            'count'       => count($events),
        ]);
    }
}
