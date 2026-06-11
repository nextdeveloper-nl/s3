<?php

namespace NextDeveloper\S3\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use NextDeveloper\Events\Services\NatsService;
use NextDeveloper\S3\Database\Models\Servers;
use NextDeveloper\S3\Database\Models\Buckets;
use NextDeveloper\S3\Database\Models\AccessKeys;
use NextDeveloper\S3\Helpers\S3KeyHelper;

/**
 * Sends commands to the storaged (seaweed) S3 agent via NATS JetStream.
 *
 * Subject: agent.s3.{uuid}.cmd
 * Protocol: see docs/agent/seaweed-nats-contract.md §C.5
 *
 * All methods publish fire-and-forget; the agent acks via agent.s3.{uuid}.evt result messages.
 */
class S3AgentCommandService
{
    /**
     * Dispatch the complete desired state to the agent on first connect or after reconcile.
     * Includes all active buckets and IAM keys for the server.
     */
    public static function fullSync(string $serverUuid): void
    {
        $server  = Servers::withoutGlobalScopes()->where('uuid', $serverUuid)->firstOrFail();
        $buckets = Buckets::withoutGlobalScopes()
            ->where('s3_server_id', $server->id)
            ->whereNull('deleted_at')
            ->get();

        $keys = AccessKeys::withoutGlobalScopes()
            ->where('s3_server_id', $server->id)
            ->where('status', 'active')
            ->get();

        static::dispatch($serverUuid, 'full_sync', [
            'buckets'  => $buckets->map(fn ($b) => [
                'name'                => $b->name,
                'versioning'          => $b->versioning ?? 'Suspended',
                'object_lock_enabled' => (bool) ($b->object_lock_enabled ?? false),
                'object_lock_mode'    => $b->object_lock_mode,
                'object_lock_days'    => $b->object_lock_days,
                'lifecycle_rules'     => $b->lifecycle_rules,
            ])->values()->all(),
            'iam_keys' => $keys->map(fn ($k) => [
                'access_key'  => $k->access_key,
                'secret_key'  => S3KeyHelper::decrypt($k->secret_key_enc),
                'role'        => $k->role,
                'bucket_acls' => $k->bucket_acls ?? (object) [],
            ])->values()->all(),
        ], timeoutS: 120);
    }

    public static function bucketCreate(string $serverUuid, array $params): void
    {
        static::dispatch($serverUuid, 'bucket_create', $params);
    }

    public static function bucketDelete(string $serverUuid, string $name): void
    {
        static::dispatch($serverUuid, 'bucket_delete', ['name' => $name]);
    }

    public static function bucketUpdate(string $serverUuid, array $params): void
    {
        static::dispatch($serverUuid, 'bucket_update', $params);
    }

    public static function iamCreate(string $serverUuid, array $key): void
    {
        static::dispatch($serverUuid, 'iam_create', $key);
    }

    public static function iamDelete(string $serverUuid, string $accessKey): void
    {
        static::dispatch($serverUuid, 'iam_delete', ['access_key' => $accessKey]);
    }

    public static function reconcile(string $serverUuid, string $scope = 'all'): void
    {
        static::dispatch($serverUuid, 'reconcile', ['scope' => $scope], timeoutS: 120);
    }

    // -------------------------------------------------------------------------

    private static function dispatch(string $serverUuid, string $operation, array $params, int $timeoutS = 30): void
    {
        $commandId = (string) Str::uuid();
        $subject   = "agent.s3.{$serverUuid}.cmd";

        $envelope = [
            'v'          => 1,
            'id'         => $commandId,
            'type'       => 'command',
            'agent_type' => 's3',
            'agent_uuid' => $serverUuid,
            'timestamp'  => time(),
            'payload'    => [
                'operation' => $operation,
                'params'    => empty($params) ? (object) [] : $params,
                'timeout_s' => $timeoutS,
            ],
        ];

        Log::info('[S3AgentCommandService] Dispatching command', [
            'subject'    => $subject,
            'command_id' => $commandId,
            'operation'  => $operation,
        ]);

        app(NatsService::class)->publish($subject, $envelope);
    }
}
