<?php

namespace NextDeveloper\S3\Services;

use NextDeveloper\IAM\Helpers\UserHelper;
use NextDeveloper\S3\Database\Models\Buckets;
use NextDeveloper\S3\Database\Models\Servers;
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
     * Block an account: marks it blocked in the DB then pushes customer_block
     * to every connected agent server where the account has active buckets.
     */
    public static function block(string $uuid, string $reason): \NextDeveloper\S3\Database\Models\Accounts
    {
        $model = \NextDeveloper\S3\Database\Models\Accounts::withoutGlobalScopes()
            ->where('uuid', $uuid)
            ->firstOrFail();

        $model->update([
            'status'         => 'blocked',
            'blocked_at'     => now(),
            'blocked_reason' => $reason,
        ]);

        // Suspend the tenant on every agent that hosts their buckets.
        static::dispatchToAccountServers($model, 'block');

        AuditLogsService::log('account.block', UserHelper::me()->uuid ?? 'system', [
            'iam_account_id' => $model->iam_account_id,
            's3_account_id'  => $model->id,
            'reason'         => $reason,
        ]);

        return $model->fresh();
    }

    /**
     * Unblock an account: restores active status in the DB then pushes
     * customer_unblock to every connected agent server.
     */
    public static function unblock(string $uuid): \NextDeveloper\S3\Database\Models\Accounts
    {
        $model = \NextDeveloper\S3\Database\Models\Accounts::withoutGlobalScopes()
            ->where('uuid', $uuid)
            ->firstOrFail();

        $model->update([
            'status'         => 'active',
            'blocked_at'     => null,
            'blocked_reason' => null,
        ]);

        // Restore the tenant's keys on every agent that hosts their buckets.
        static::dispatchToAccountServers($model, 'unblock');

        AuditLogsService::log('account.unblock', UserHelper::me()->uuid ?? 'system', [
            'iam_account_id' => $model->iam_account_id,
            's3_account_id'  => $model->id,
        ]);

        return $model->fresh();
    }

    /**
     * Suspends the S3 account belonging to the given IAM account, if one
     * exists. S3 accounts are opt-in (created on first subscription), so a
     * customer without an S3 account is a no-op here, not an error.
     */
    public static function suspendWithIamAccount(\NextDeveloper\IAM\Database\Models\Accounts $account): ?\NextDeveloper\S3\Database\Models\Accounts
    {
        $s3Account = \NextDeveloper\S3\Database\Models\Accounts::withoutGlobalScopes()
            ->where('iam_account_id', $account->id)
            ->first();

        if (!$s3Account) {
            return null;
        }

        return self::block($s3Account->uuid, 'Account suspended.');
    }

    /**
     * Dispatch customer_block or customer_unblock to every connected agent
     * server that hosts at least one active bucket for this account.
     */
    private static function dispatchToAccountServers(
        \NextDeveloper\S3\Database\Models\Accounts $account,
        string $operation
    ): void {
        $serverIds = Buckets::withoutGlobalScopes()
            ->where('s3_account_id', $account->id)
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('s3_server_id');

        if ($serverIds->isEmpty()) {
            return;
        }

        $servers = Servers::withoutGlobalScopes()
            ->whereIn('id', $serverIds)
            ->where('agent_status', 'connected')
            ->get();

        foreach ($servers as $server) {
            if ($operation === 'block') {
                S3AgentCommandService::customerBlock($server->uuid, $account->uuid);
            } else {
                S3AgentCommandService::customerUnblock($server->uuid, $account->uuid);
            }
        }
    }
}