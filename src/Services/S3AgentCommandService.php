<?php

namespace NextDeveloper\S3\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use NextDeveloper\Events\Services\NatsService;
use NextDeveloper\S3\Database\Models\Accounts;
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

        // AccessKeys belong to accounts, not directly to servers.
        // Collect account IDs from the buckets already fetched for this server.
        $accountIds = $buckets->pluck('s3_account_id')->filter()->unique()->values();

        // Load accounts so we can resolve UUIDs for owner_tenant_id.
        $accounts = Accounts::withoutGlobalScopes()
            ->whereIn('id', $accountIds)
            ->get(['id', 'uuid'])
            ->keyBy('id');

        $keys = AccessKeys::withoutGlobalScopes()
            ->whereIn('s3_account_id', $accountIds)
            ->where('status', 'active')
            ->get();

        static::dispatch($serverUuid, 'full_sync', [
            'buckets'  => $buckets->map(fn ($b) => [
                'name'                => $b->bucket_name ?? $b->name,
                'bucket_id'           => $b->uuid,
                'owner_tenant_id'     => $accounts->get($b->s3_account_id)?->uuid ?? '',
                'versioning'          => $b->versioning ?? 'Suspended',
                'object_lock_enabled'    => (bool) ($b->object_lock_enabled ?? false),
                'object_lock_mode'       => $b->object_lock_mode,
                'object_lock_days'       => $b->object_lock_days,
                'lifecycle_rules'        => $b->lifecycle_rules,
                'audit_enabled'          => (bool) ($b->is_object_audit_enabled ?? false),
            ])->values()->all(),
            'iam_keys' => $keys->map(fn ($k) => [
                'access_key'      => $k->access_key,
                'secret_key'      => S3KeyHelper::decrypt($k->secret_key_enc),
                'role'            => $k->role,
                'bucket_acls'     => $k->bucket_acls ?? [],
                // owner_tenant_id lets the agent map keys to tenants for customer_block/unblock.
                'owner_tenant_id' => $accounts->get($k->s3_account_id)?->uuid ?? '',
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

    /**
     * Enable versioning on a standard (non-WORM) bucket.
     * Once enabled, versioning can only be suspended — never fully removed.
     */
    public static function bucketVersioningEnable(string $serverUuid, string $bucketName): void
    {
        static::dispatch($serverUuid, 'bucket_versioning_enable', ['name' => $bucketName]);
    }

    /**
     * Suspend versioning on a standard (non-WORM) bucket.
     * Existing object versions are preserved; new PUTs will no longer create versions.
     */
    public static function bucketVersioningSuspend(string $serverUuid, string $bucketName): void
    {
        static::dispatch($serverUuid, 'bucket_versioning_suspend', ['name' => $bucketName]);
    }

    // -------------------------------------------------------------------------
    // WORM / Object Lock operations
    // -------------------------------------------------------------------------

    /**
     * Create a bucket with object lock enabled.
     * Required params: name, object_lock_mode (COMPLIANCE|GOVERNANCE), retention_days.
     */
    public static function wormBucketCreate(string $serverUuid, array $params): void
    {
        static::dispatch($serverUuid, 'worm_bucket_create', $params);
    }

    /**
     * Update WORM retention settings on an existing object-lock bucket.
     * Required params: name. Optional: object_lock_mode, retention_days.
     */
    public static function wormBucketUpdate(string $serverUuid, array $params): void
    {
        static::dispatch($serverUuid, 'worm_bucket_update', $params);
    }

    /**
     * Delete a WORM bucket (agent enforces that all objects must be expired first).
     */
    public static function wormBucketDelete(string $serverUuid, string $name): void
    {
        static::dispatch($serverUuid, 'worm_bucket_delete', ['name' => $name]);
    }

    // -------------------------------------------------------------------------
    // Customer block / unblock
    // -------------------------------------------------------------------------

    /**
     * Block a tenant on the agent: suspends all their IAM keys so every
     * S3 request returns 403 until they are unblocked.
     * The agent identifies the tenant's keys via owner_tenant_id sent in full_sync.
     */
    public static function customerBlock(string $serverUuid, string $ownerTenantUuid): void
    {
        static::dispatch($serverUuid, 'customer_block', ['owner_tenant_id' => $ownerTenantUuid]);
    }

    /**
     * Unblock a tenant on the agent: restores all their IAM keys.
     */
    public static function customerUnblock(string $serverUuid, string $ownerTenantUuid): void
    {
        static::dispatch($serverUuid, 'customer_unblock', ['owner_tenant_id' => $ownerTenantUuid]);
    }

    public static function iamCreate(string $serverUuid, array $key): void
    {
        static::dispatch($serverUuid, 'iam_create', $key);
    }

    /**
     * $name must be the identity's IAM name (what iam_create sent as "name"),
     * NOT the access key — the agent's DeleteIAMUser() matches on identity
     * name, not access key (dispatcher.go: `case "iam_delete": ...DeleteIAMUser(p.Name)`).
     * Sending the access key under the wrong param key here previously meant
     * every call resolved to an empty name — and since DeleteIAMUser() removes
     * every identity whose Name matches, not just the first, and every
     * identity's Name was blank before the recent name-field fix, this
     * deleted every non-admin identity on the server in a single call.
     */
    public static function iamDelete(string $serverUuid, string $name): void
    {
        static::dispatch($serverUuid, 'iam_delete', ['name' => $name]);
    }

    public static function reconcile(string $serverUuid, string $scope = 'all'): void
    {
        static::dispatch($serverUuid, 'reconcile', ['scope' => $scope], timeoutS: 120);
    }

    /**
     * Pull current stats for one bucket (or all buckets when $bucketName is null).
     * The agent responds with a result containing an output.buckets array.
     */
    public static function bucketStats(string $serverUuid, ?string $bucketName = null): void
    {
        $params = $bucketName !== null ? ['bucket' => $bucketName] : [];
        static::dispatch($serverUuid, 's3.bucket.stats', $params);
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
