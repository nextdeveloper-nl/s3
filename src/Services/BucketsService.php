<?php

namespace NextDeveloper\S3\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use NextDeveloper\Commons\Exceptions\NotAllowedException;
use NextDeveloper\IAM\Helpers\UserHelper;
use NextDeveloper\S3\Database\Models\Accounts;
use NextDeveloper\S3\Database\Models\Buckets;
use NextDeveloper\S3\Database\Models\Servers;
use NextDeveloper\S3\Services\AbstractServices\AbstractBucketsService;
use NextDeveloper\S3\Services\AccountsService;
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
        // Auto-generate bucket_name from name if not provided.
        // bucket_name is the actual S3 identifier on SeaweedFS; name is the customer-facing label.
        if (empty($data['bucket_name'])) {
            $data['bucket_name'] = Str::slug($data['name'] ?? '');
        }

        // Validate bucket_name: lowercase alphanumeric + hyphens only, 3–63 chars
        if (!preg_match('/^[a-z0-9][a-z0-9\-]{1,61}[a-z0-9]$/', $data['bucket_name'])) {
            throw new NotAllowedException(
                'bucket_name must be 3–63 lowercase alphanumeric characters or hyphens, and cannot start or end with a hyphen.'
            );
        }

        // Resolve s3_account_id from the current IAM account if not explicitly provided.
        // If no S3 account exists yet, provision one automatically so the customer
        // does not need a separate onboarding step before creating their first bucket.
        if (empty($data['s3_account_id'])) {
            $iamAccount = UserHelper::currentAccount();
            $s3Account = Accounts::withoutGlobalScopes()
                ->where('iam_account_id', $iamAccount->id)
                ->first();

            if (!$s3Account) {
                UserHelper::runAsAdmin(function () use ($iamAccount, &$s3Account) {
                    $s3Account = AccountsService::create([
                        'iam_account_id' => $iamAccount->id,
                        'iam_user_id'    => UserHelper::me()->id,
                        'slug'           => $iamAccount->slug ?? $iamAccount->uuid,
                    ]);
                });
            }

            $data['s3_account_id'] = $s3Account->id;
        }

        // Enforce max_buckets quota for the account.
        // s3_account_id may be a UUID string (from API) or an integer (resolved above).
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

            // Hard billing gate: refuse to create the bucket at all unless the
            // account ends up with an active subscription on this server —
            // auto-subscribing to Pay-As-You-Go if it has none yet. Throws if
            // the server has no Marketplace product, or no PAYG catalog entry,
            // so usage can never start accruing with no billing anchor (see
            // AccountsService::ensurePaygSubscriptionOrFail()).
            if ($account && !empty($data['s3_server_id'])) {
                $serverRef = $data['s3_server_id'];
                $server = is_numeric($serverRef)
                    ? Servers::withoutGlobalScopes()->find((int) $serverRef)
                    : Servers::withoutGlobalScopes()->where('uuid', $serverRef)->first();

                if ($server) {
                    AccountsService::ensurePaygSubscriptionOrFail($account, $server);
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
                    'name'             => $model->bucket_name,
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
                    'name'            => $model->bucket_name,
                    'bucket_id'       => $model->uuid,
                    'lifecycle_rules' => $model->lifecycle_rules,
                    'audit_enabled'   => (bool) ($model->is_object_audit_enabled ?? false),
                ]);

                // Versioning must be enabled via a separate command after bucket creation.
                if (($model->versioning ?? 'Suspended') === 'Enabled') {
                    S3AgentCommandService::bucketVersioningEnable($server->uuid, $model->bucket_name);
                }
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
        // Both object_lock_enabled and bucket_name are immutable after creation — strip silently.
        unset($data['object_lock_enabled'], $data['bucket_name']);

        $model = parent::update($id, $data);

        $server = Servers::withoutGlobalScopes()->find($model->s3_server_id);

        if ($server) {
            if (!empty($model->object_lock_enabled)) {
                $ownerUuid = Accounts::withoutGlobalScopes()
                    ->find($model->s3_account_id)
                    ?->uuid ?? '';

                // WORM bucket: relay retention policy changes to the agent.
                S3AgentCommandService::wormBucketUpdate($server->uuid, [
                    'name'             => $model->bucket_name,
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
                    'name'            => $model->bucket_name,
                    'lifecycle_rules' => $model->lifecycle_rules,
                    'audit_enabled'   => (bool) ($model->is_object_audit_enabled ?? false),
                ]);

                // Send explicit versioning command when the caller provided a versioning value.
                // bucket_update does not toggle versioning; a dedicated command is required.
                if (array_key_exists('versioning', $data)) {
                    if ($model->versioning === 'Enabled') {
                        S3AgentCommandService::bucketVersioningEnable($server->uuid, $model->bucket_name);
                    } else {
                        S3AgentCommandService::bucketVersioningSuspend($server->uuid, $model->bucket_name);
                    }
                }
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

        $name     = $model->bucket_name;
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