<?php

namespace NextDeveloper\S3\Authorization\Roles;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use NextDeveloper\Commons\Helpers\DatabaseHelper;
use NextDeveloper\IAM\Authorization\Roles\AbstractRole;
use NextDeveloper\IAM\Authorization\Roles\IAuthorizationRole;
use NextDeveloper\IAM\Database\Models\Users;
use NextDeveloper\IAM\Helpers\UserHelper;

class S3ManagerRole extends AbstractRole implements IAuthorizationRole
{
    public const NAME = 's3-manager';

    public const LEVEL = 150;

    public const DESCRIPTION = 'S3 manager with full CRUD access to S3 resources within their account. Read-only access to servers and infrastructure stats.';

    public const DB_PREFIX = 's3';

    private const PERSPECTIVES = [
        's3_access_keys_perspective',
        's3_accounts_perspective',
        's3_buckets_perspective',
        's3_servers_perspective',
        's3_worm_expiring_perspective',
    ];

    /**
     * Restricts queries to records belonging to the current account.
     * Perspectives are not filtered — they provide their own scoping.
     */
    public function apply(Builder $builder, Model $model)
    {
        if (DatabaseHelper::isColumnExists($model->getTable(), 'iam_account_id')) {
            $builder->where('iam_account_id', UserHelper::currentAccount()->id);
        }
    }

    public function checkPrivileges(?Users $users = null)
    {
        //
    }

    public function getModule()
    {
        return 's3';
    }

    public function allowedOperations(): array
    {
        return [
            // Servers — read-only, infrastructure is admin-managed
            's3_servers:read',

            // Accounts — read-only, provisioned by admin
            's3_accounts:read',

            // Account stats — read-only
            's3_account_stats:read',

            // Buckets
            's3_buckets:read',
            's3_buckets:create',
            's3_buckets:update',
            's3_buckets:delete',

            // Access keys
            's3_access_keys:read',
            's3_access_keys:create',
            's3_access_keys:update',
            's3_access_keys:delete',

            // Webhooks
            's3_webhooks:read',
            's3_webhooks:create',
            's3_webhooks:update',
            's3_webhooks:delete',

            // Backup agents — full access within the account
            's3_backup_agents:read',
            's3_backup_agents:create',
            's3_backup_agents:update',
            's3_backup_agents:delete',

            // Backup jobs — full access within the account
            's3_backup_jobs:read',
            's3_backup_jobs:create',
            's3_backup_jobs:update',
            's3_backup_jobs:delete',

            // Backup job runs — read-only; written by the agent/platform, never by a user form
            's3_backup_job_runs:read',

            // Webhook deliveries — read-only
            's3_webhook_deliveries:read',

            // WORM commitments
            's3_worm_commitments:read',
            's3_worm_commitments:create',
            's3_worm_commitments:update',
            's3_worm_commitments:delete',

            // Multipart uploads — read + delete (abort)
            's3_multipart_uploads:read',
            's3_multipart_uploads:delete',

            // Audit logs — read-only
            's3_audit_logs:read',

            // Bandwidth monthlies — read-only
            's3_bandwidth_monthlies:read',

            // Deposit ledgers — read-only
            's3_deposit_ledgers:read',

            // Server capacity stats — read-only
            's3_server_capacity_stats:read',

            // Server telemetries — read-only
            's3_server_telemetries:read',

            // Usage daily stats — read-only
            's3_usage_daily_stats:read',

            // Usage snapshots — read-only
            's3_usage_snapshots:read',

            // Notifications sent — read-only
            's3_notifications_sents:read',

            // Perspectives (read-only)
            's3_access_keys_perspective:read',
            's3_accounts_perspective:read',
            's3_buckets_perspective:read',
            's3_servers_perspective:read',
            's3_worm_expiring_perspective:read',
        ];
    }

    /**
     * Managers can update any S3 resource that belongs to their account.
     */
    public function checkUpdatePolicy(Model $model, Users $user): bool
    {
        if (UserHelper::hasRole('system-admin')) {
            return true;
        }

        $operation = $model->getTable() . ':update';

        if (in_array('!' . $operation, $this->allowedOperations())) {
            return true;
        }

        if (!in_array($operation, $this->allowedOperations())) {
            return false;
        }

        if (DatabaseHelper::isColumnExists($model->getTable(), 'iam_account_id')) {
            return $model->iam_account_id == UserHelper::currentAccount()->id;
        }

        return true;
    }

    /**
     * Managers can delete any S3 resource that belongs to their account.
     */
    public function checkDeletePolicy(Model $model, Users $user): bool
    {
        if (UserHelper::hasRole('system-admin')) {
            return true;
        }

        $operation = $model->getTable() . ':delete';

        if (in_array('!' . $operation, $this->allowedOperations())) {
            return true;
        }

        if (!in_array($operation, $this->allowedOperations())) {
            return false;
        }

        if (DatabaseHelper::isColumnExists($model->getTable(), 'iam_account_id')) {
            return $model->iam_account_id == UserHelper::currentAccount()->id;
        }

        return true;
    }

    public function getLevel(): int
    {
        return self::LEVEL;
    }

    public function getDescription(): string
    {
        return self::DESCRIPTION;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function canBeApplied(mixed $column): bool
    {
        if (self::DB_PREFIX === '*') {
            return true;
        }

        if (Str::startsWith($column, self::DB_PREFIX)) {
            return true;
        }

        return false;
    }

    public function getDbPrefix()
    {
        return self::DB_PREFIX;
    }

    public function checkRules(Users $_users): bool
    {
        return true;
    }
}
