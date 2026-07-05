<?php

namespace NextDeveloper\S3\Services;

use Illuminate\Support\Facades\Log;
use NextDeveloper\Commons\Exceptions\NotAllowedException;
use NextDeveloper\IAM\Helpers\UserHelper;
use NextDeveloper\S3\Database\Models\AccessKeys;
use NextDeveloper\S3\Database\Models\Buckets;
use NextDeveloper\S3\Database\Models\Servers;
use NextDeveloper\S3\Helpers\S3KeyHelper;
use NextDeveloper\S3\Services\AbstractServices\AbstractAccessKeysService;
use NextDeveloper\S3\Services\S3AgentCommandService;

/**
 * This class is responsible from managing the data for AccessKeys
 *
 * Class AccessKeysService.
 *
 * @package NextDeveloper\S3\Database\Models
 */
class AccessKeysService extends AbstractAccessKeysService
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * Generate a key pair, persist it, then push iam_create to every server
     * that hosts buckets for this account.
     *
     * The returned model has a transient `plain_secret` attribute so the caller
     * can show it once — it is never stored in plaintext.
     */
    public static function create(array $data)
    {
        $pair = S3KeyHelper::generate();

        $data['access_key']     = $pair['access_key'];
        $data['secret_key_enc'] = S3KeyHelper::encrypt($pair['secret_key']);
        $data['status']         = $data['status'] ?? 'active';

        // Normalize friendly role aliases to the agent-side canonical names.
        $data['role'] = match ($data['role'] ?? 'readwrite') {
            'full_access' => 'readwrite',
            'read_only'   => 'readonly',
            'write_only'  => 'writeonly',
            'no_delete'   => 'nodelete',
            default       => $data['role'] ?? 'readwrite',
        };

        $model = parent::create($data);

        // Attach plaintext secret as a transient attribute — visible only now
        $model->plain_secret = $pair['secret_key'];

        // Push iam_create to every server that has buckets for this account.
        // "name" was never sent before this — every IAM identity's Name/UserID
        // came back blank in s3.json regardless of what the caller intended,
        // making identities impossible to tell apart administratively.
        static::dispatchIamToServers($model->s3_account_id, function (string $serverUuid) use ($model, $pair) {
            S3AgentCommandService::iamCreate($serverUuid, [
                'access_key'  => $model->access_key,
                'secret_key'  => $pair['secret_key'],
                'name'        => $model->name ?? $model->access_key,
                'role'        => $model->role        ?? 'readwrite',
                'bucket_acls' => $model->bucket_acls ?? [],
            ]);
        });

        return $model;
    }

    /**
     * Delete a key from the DB, then push iam_delete to every relevant server.
     */
    public static function delete($id)
    {
        $model = AccessKeys::withoutGlobalScopes()->where('uuid', $id)->first();

        if (!$model) {
            throw new NotAllowedException(
                'We cannot find the related object to delete. ' .
                'Maybe you dont have the permission to delete this object?'
            );
        }

        // Must match whatever iam_create sent as "name" — the agent deletes by
        // identity name, not access key (see S3AgentCommandService::iamDelete()).
        $identityName = $model->name ?? $model->access_key;
        $accountId    = $model->s3_account_id;

        parent::delete($id);

        static::dispatchIamToServers($accountId, function (string $serverUuid) use ($identityName) {
            S3AgentCommandService::iamDelete($serverUuid, $identityName);
        });

        return true;
    }

    /**
     * Revoke a key so it can no longer authenticate.
     *
     * Previously only flipped our own DB row — the real SeaweedFS identity
     * was never touched, so a "revoked" key kept authenticating forever.
     * There's no separate disable/suspend command on the storage agent side
     * (only iam_create/iam_delete), so revoke pushes iam_delete exactly like
     * delete() does; our record just stays around as `revoked` instead of
     * being removed, preserving the audit trail delete() doesn't have.
     */
    public static function revoke(string $uuid, string $reason = ''): AccessKeys
    {
        $model = AccessKeys::where('uuid', $uuid)->firstOrFail();

        $model->update([
            'status'         => 'revoked',
            'revoked_at'     => now(),
            'revoked_reason' => $reason,
        ]);

        // Must match whatever iam_create sent as "name" — the agent deletes by
        // identity name, not access key.
        $identityName = $model->name ?? $model->access_key;

        static::dispatchIamToServers($model->s3_account_id, function (string $serverUuid) use ($identityName) {
            S3AgentCommandService::iamDelete($serverUuid, $identityName);
        });

        AuditLogsService::log('key.revoke', UserHelper::me()->uuid ?? 'system', [
            's3_account_id'    => $model->s3_account_id,
            's3_access_key_id' => $model->id,
            'reason'           => $reason,
        ]);

        return $model->fresh();
    }

    /**
     * Decrypt and return the secret key — admin-only, single reveal.
     */
    public static function reveal(string $uuid): string
    {
        $model = AccessKeys::where('uuid', $uuid)->firstOrFail();

        AuditLogsService::log('key.reveal', UserHelper::me()->uuid ?? 'system', [
            's3_account_id'    => $model->s3_account_id,
            's3_access_key_id' => $model->id,
        ]);

        return S3KeyHelper::decrypt($model->secret_key_enc);
    }

    /**
     * Find every server that has at least one active bucket for the given account
     * and invoke $callback with each server UUID.
     */
    private static function dispatchIamToServers(int $accountId, callable $callback): void
    {
        $serverIds = Buckets::withoutGlobalScopes()
            ->where('s3_account_id', $accountId)
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('s3_server_id')
            ->filter();

        if ($serverIds->isEmpty()) {
            Log::info('[AccessKeysService] No servers found for account — skipping iam command', [
                's3_account_id' => $accountId,
            ]);
            return;
        }

        foreach ($serverIds as $serverId) {
            $server = Servers::withoutGlobalScopes()->find($serverId);

            if ($server) {
                $callback($server->uuid);
            }
        }
    }
}