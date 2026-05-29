<?php

namespace NextDeveloper\S3\Services;

use NextDeveloper\IAM\Helpers\UserHelper;
use NextDeveloper\S3\Services\AbstractServices\AbstractAccountsService;

/**
 * This class is responsible from managing the data for Accounts
 *
 * Class AccountsService.
 *
 * @package NextDeveloper\S3\Database\Models
 */
class AccountsService extends AbstractAccountsService
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * Create an S3 account. Enforces unique slug and sets defaults.
     */
    public static function create(array $data)
    {
        // Ensure slug is unique across non-deleted accounts
        if (!empty($data['slug'])) {
            $exists = \NextDeveloper\S3\Database\Models\Accounts::where('slug', $data['slug'])
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                throw new \NextDeveloper\Commons\Exceptions\NotAllowedException(
                    "An S3 account with slug '{$data['slug']}' already exists."
                );
            }
        }

        // Default quota values (can be overridden per account)
        $data['status'] = 'active';
        $data['quota_storage_bytes']  = $data['quota_storage_bytes']  ?? config('s3.defaults.quota_storage_bytes', 10737418240);  // 10 GB
        $data['quota_egress_bytes_mo'] = $data['quota_egress_bytes_mo'] ?? config('s3.defaults.quota_egress_bytes_mo', 107374182400); // 100 GB
        $data['quota_max_buckets']    = $data['quota_max_buckets']    ?? config('s3.defaults.quota_max_buckets', 10);
        $data['quota_max_objects']    = $data['quota_max_objects']    ?? config('s3.defaults.quota_max_objects', 1000000);

        return parent::create($data);
    }

    /**
     * Block an account, preventing all S3 operations.
     */
    public static function block(string $uuid, string $reason): \NextDeveloper\S3\Database\Models\Accounts
    {
        $model = \NextDeveloper\S3\Database\Models\Accounts::where('uuid', $uuid)->firstOrFail();

        $model->update([
            'status'         => 'blocked',
            'blocked_at'     => now(),
            'blocked_reason' => $reason,
        ]);

        AuditLogsService::log('account.block', UserHelper::me()->uuid ?? 'system', [
            's3_account_id' => $model->id,
            'reason'        => $reason,
        ]);

        return $model->fresh();
    }

    /**
     * Remove a block and restore the account to active status.
     */
    public static function unblock(string $uuid): \NextDeveloper\S3\Database\Models\Accounts
    {
        $model = \NextDeveloper\S3\Database\Models\Accounts::where('uuid', $uuid)->firstOrFail();

        $model->update([
            'status'         => 'active',
            'blocked_at'     => null,
            'blocked_reason' => null,
        ]);

        AuditLogsService::log('account.unblock', UserHelper::me()->uuid ?? 'system', [
            's3_account_id' => $model->id,
        ]);

        return $model->fresh();
    }
}