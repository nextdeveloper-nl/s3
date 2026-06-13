<?php

namespace NextDeveloper\S3\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use NextDeveloper\Commons\Exceptions\NotAllowedException;
use NextDeveloper\S3\Database\Models\Accounts;
use NextDeveloper\S3\Database\Models\Buckets;
use NextDeveloper\S3\Database\Models\Servers;
use NextDeveloper\S3\Services\AbstractServices\AbstractBucketsService;
use NextDeveloper\S3\Services\S3AgentCommandService;
use NextDeveloper\S3\Services\WormCommitmentsService;

/**
 * This class is responsible from managing the data for Buckets
 *
 * Class BucketsService.
 *
 * @package NextDeveloper\S3\Database\Models
 */
class BucketsService extends AbstractBucketsService
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * Create a bucket, persist it, then push the bucket_create command to the agent.
     */
    public static function create(array $data)
    {
        // Validate bucket name: lowercase alphanumeric + hyphens only, 3–63 chars
        if (!empty($data['name']) && !preg_match('/^[a-z0-9][a-z0-9\-]{1,61}[a-z0-9]$/', $data['name'])) {
            throw new NotAllowedException(
                'Bucket name must be 3–63 lowercase alphanumeric characters or hyphens, and cannot start or end with a hyphen.'
            );
        }

        // Enforce max_buckets quota for the account.
        // s3_account_id arrives as a UUID string from the API layer; look it up by UUID.
        if (!empty($data['s3_account_id'])) {
            $accountRef = $data['s3_account_id'];
            $account = is_numeric($accountRef)
                ? Accounts::withoutGlobalScopes()->find((int) $accountRef)
                : Accounts::withoutGlobalScopes()->where('uuid', $accountRef)->first();

            if ($account && $account->quota_max_buckets > 0) {
                $currentCount = Buckets::withoutGlobalScopes()
                    ->where('s3_account_id', $account->id)
                    ->whereNull('deleted_at')
                    ->count();

                if ($currentCount >= $account->quota_max_buckets) {
                    throw new NotAllowedException(
                        "Bucket quota exceeded: this account allows a maximum of {$account->quota_max_buckets} buckets."
                    );
                }
            }
        }

        $data['status']         = $data['status']         ?? 'active';
        $data['object_count']   = $data['object_count']   ?? 0;
        $data['size_bytes']     = $data['size_bytes']      ?? 0;
        $data['replica_health'] = $data['replica_health']  ?? 'unknown';

        $model = parent::create($data);

        // Dispatch bucket_create to the server agent after the DB record is saved.
        $server = Servers::withoutGlobalScopes()->find($model->s3_server_id);

        if ($server) {
            // WORM buckets need a dedicated agent command so SeaweedFS enables object lock at creation.
            // A regular bucket cannot be converted to WORM after creation.
            if (!empty($model->object_lock_enabled)) {
                // Resolve account UUID for owner_tenant_id (required by agent protocol).
                $ownerUuid = Accounts::withoutGlobalScopes()
                    ->find($model->s3_account_id)
                    ?->uuid ?? '';

                S3AgentCommandService::wormBucketCreate($server->uuid, [
                    'name'             => $model->name,
                    'bucket_id'        => $model->uuid,
                    'owner_tenant_id'  => $ownerUuid,
                    'object_lock_mode' => $model->object_lock_mode ?? 'COMPLIANCE',
                    'retention_days'   => (int) ($model->object_lock_days ?? 1),
                    'audit_enabled'    => (bool) ($model->is_object_audit_enabled ?? false),
                ]);

                // Record the retention commitment in the platform ledger.
                WormCommitmentsService::createFromBucket($model);
            } else {
                S3AgentCommandService::bucketCreate($server->uuid, [
                    'name'            => $model->name,
                    'bucket_id'       => $model->uuid,
                    'versioning'      => $model->versioning ?? 'Suspended',
                    'lifecycle_rules' => $model->lifecycle_rules,
                    'audit_enabled'   => (bool) ($model->is_object_audit_enabled ?? false),
                ]);
            }
        } else {
            Log::warning('[BucketsService] No server found for bucket — skipping agent command', [
                'bucket_uuid'  => $model->uuid,
                's3_server_id' => $model->s3_server_id,
            ]);
        }

        return $model;
    }

    /**
     * Update a bucket, persist it, then push the appropriate command to the agent.
     *
     * object_lock_enabled is immutable post-creation; object_lock_mode/object_lock_days
     * can be updated on WORM buckets via worm_bucket_update.
     */
    public static function update($id, array $data)
    {
        // object_lock_enabled cannot be changed after creation — strip it silently.
        unset($data['object_lock_enabled']);

        $model = parent::update($id, $data);

        $server = Servers::withoutGlobalScopes()->find($model->s3_server_id);

        if ($server) {
            if (!empty($model->object_lock_enabled)) {
                $ownerUuid = Accounts::withoutGlobalScopes()
                    ->find($model->s3_account_id)
                    ?->uuid ?? '';

                // WORM bucket: relay retention policy changes to the agent.
                S3AgentCommandService::wormBucketUpdate($server->uuid, [
                    'name'             => $model->name,
                    'bucket_id'        => $model->uuid,
                    'owner_tenant_id'  => $ownerUuid,
                    'object_lock_mode' => $model->object_lock_mode,
                    'retention_days'   => (int) ($model->object_lock_days ?? 1),
                    'audit_enabled'    => (bool) ($model->is_object_audit_enabled ?? false),
                ]);

                // Supersede old commitment and write a new one if retention policy changed.
                // Throws NotAllowedException if a COMPLIANCE period is shortened.
                WormCommitmentsService::supersede($model);
            } else {
                S3AgentCommandService::bucketUpdate($server->uuid, [
                    'name'            => $model->name,
                    'versioning'      => $model->versioning,
                    'lifecycle_rules' => $model->lifecycle_rules,
                    'audit_enabled'   => (bool) ($model->is_object_audit_enabled ?? false),
                ]);
            }
        }

        return $model;
    }

    /**
     * Delete a bucket from the DB, then push bucket_delete to the agent.
     */
    public static function delete($id)
    {
        // Load before deleting so we have the name and server reference.
        $model = Buckets::withoutGlobalScopes()->where('uuid', $id)->first();

        if (!$model) {
            throw new NotAllowedException(
                'We cannot find the related object to delete. ' .
                'Maybe you dont have the permission to delete this object?'
            );
        }

        $name     = $model->name;
        $serverId = $model->s3_server_id;
        $isWorm   = !empty($model->object_lock_enabled);

        // Gate: a COMPLIANCE commitment that has not yet expired blocks deletion.
        // GOVERNANCE commitments are cancelled (with pro-rata refund) on delete.
        if ($isWorm) {
            $activeCommitment = WormCommitmentsService::getActiveForBucket($model->id);

            if ($activeCommitment) {
                $locksUntil = Carbon::parse($activeCommitment->locks_until);

                if (strtoupper($activeCommitment->mode) === 'COMPLIANCE' && $locksUntil->isFuture()) {
                    throw new NotAllowedException(
                        'Cannot delete a bucket with an active COMPLIANCE retention commitment. ' .
                        'Retention expires ' . $locksUntil->toIso8601String() . '.'
                    );
                }

                if (strtoupper($activeCommitment->mode) === 'GOVERNANCE') {
                    // Cancel the GOVERNANCE commitment and issue a pro-rata refund.
                    WormCommitmentsService::cancel($activeCommitment->uuid);
                }
            }
        }

        parent::delete($id);

        $server = Servers::withoutGlobalScopes()->find($serverId);

        if ($server) {
            // WORM buckets require worm_bucket_delete so the agent can enforce
            // that all objects have expired before removing the bucket.
            if ($isWorm) {
                S3AgentCommandService::wormBucketDelete($server->uuid, $name);
            } else {
                S3AgentCommandService::bucketDelete($server->uuid, $name);
            }
        }

        return true;
    }
}