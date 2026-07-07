<?php

namespace NextDeveloper\S3\Authorization\Roles;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use NextDeveloper\IAM\Authorization\Roles\AbstractRole;
use NextDeveloper\IAM\Authorization\Roles\IAuthorizationRole;
use NextDeveloper\IAM\Database\Models\Users;

class S3AdminRole extends AbstractRole implements IAuthorizationRole
{
    public const NAME = 's3-admin';

    public const LEVEL = 100;

    public const DESCRIPTION = 'S3 administrator with full access to all S3 objects across all accounts.';

    public const DB_PREFIX = 's3';

    /**
     * Admin sees everything — no additional WHERE conditions applied.
     */
    public function apply(Builder $builder, Model $model)
    {
        //  No restrictions for admin
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
            // Servers — infrastructure management
            's3_servers:read',
            's3_servers:create',
            's3_servers:update',
            's3_servers:delete',

            // Accounts — S3 tenant accounts
            's3_accounts:read',
            's3_accounts:create',
            's3_accounts:update',
            's3_accounts:delete',

            // Account stats
            's3_account_stats:read',
            's3_account_stats:create',
            's3_account_stats:update',
            's3_account_stats:delete',

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

            // Backup agents — cross-account administration
            's3_backup_agents:read',
            's3_backup_agents:create',
            's3_backup_agents:update',
            's3_backup_agents:delete',

            // Backup jobs — cross-account administration
            's3_backup_jobs:read',
            's3_backup_jobs:create',
            's3_backup_jobs:update',
            's3_backup_jobs:delete',

            // Backup job runs — admin gets full CRUD for support/cleanup, same asymmetry as audit_logs/webhook_deliveries
            's3_backup_job_runs:read',
            's3_backup_job_runs:create',
            's3_backup_job_runs:update',
            's3_backup_job_runs:delete',

            // Webhook deliveries
            's3_webhook_deliveries:read',
            's3_webhook_deliveries:create',
            's3_webhook_deliveries:update',
            's3_webhook_deliveries:delete',

            // WORM commitments
            's3_worm_commitments:read',
            's3_worm_commitments:create',
            's3_worm_commitments:update',
            's3_worm_commitments:delete',

            // Multipart uploads
            's3_multipart_uploads:read',
            's3_multipart_uploads:create',
            's3_multipart_uploads:update',
            's3_multipart_uploads:delete',

            // Audit logs
            's3_audit_logs:read',
            's3_audit_logs:create',
            's3_audit_logs:update',
            's3_audit_logs:delete',

            // Bandwidth monthlies
            's3_bandwidth_monthlies:read',
            's3_bandwidth_monthlies:create',
            's3_bandwidth_monthlies:update',
            's3_bandwidth_monthlies:delete',

            // Deposit ledgers
            's3_deposit_ledgers:read',
            's3_deposit_ledgers:create',
            's3_deposit_ledgers:update',
            's3_deposit_ledgers:delete',

            // Server capacity stats
            's3_server_capacity_stats:read',
            's3_server_capacity_stats:create',
            's3_server_capacity_stats:update',
            's3_server_capacity_stats:delete',

            // Server telemetries
            's3_server_telemetries:read',
            's3_server_telemetries:create',
            's3_server_telemetries:update',
            's3_server_telemetries:delete',

            // Usage daily stats
            's3_usage_daily_stats:read',
            's3_usage_daily_stats:create',
            's3_usage_daily_stats:update',
            's3_usage_daily_stats:delete',

            // Usage snapshots
            's3_usage_snapshots:read',
            's3_usage_snapshots:create',
            's3_usage_snapshots:update',
            's3_usage_snapshots:delete',

            // Notifications sent
            's3_notifications_sents:read',
            's3_notifications_sents:create',
            's3_notifications_sents:update',
            's3_notifications_sents:delete',

            // Perspectives (read-only cross-account views)
            's3_access_keys_perspective:read',
            's3_accounts_perspective:read',
            's3_buckets_perspective:read',
            's3_quota_alerts_perspective:read',
            's3_servers_perspective:read',
            's3_worm_expiring_perspective:read',
        ];
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
