<?php

namespace NextDeveloper\S3\Services;

use NextDeveloper\S3\Services\AbstractServices\AbstractAuditLogsService;

/**
 * This class is responsible from managing the data for AuditLogs
 *
 * Class AuditLogsService.
 *
 * @package NextDeveloper\S3\Database\Models
 */
class AuditLogsService extends AbstractAuditLogsService
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * Audit log is immutable — updates are not allowed.
     */
    public static function update($id, array $data)
    {
        throw new \NextDeveloper\Commons\Exceptions\NotAllowedException(
            'Audit log entries are immutable and cannot be updated.'
        );
    }

    /**
     * Audit log is immutable — deletes are not allowed.
     */
    public static function delete($id)
    {
        throw new \NextDeveloper\Commons\Exceptions\NotAllowedException(
            'Audit log entries are immutable and cannot be deleted.'
        );
    }

    /**
     * Write an immutable audit log entry.
     *
     * @param  string       $action       e.g. 'account.block', 'key.revoke', 'worm.cancel'
     * @param  string|null  $performedBy  UUID of the actor (user or system)
     * @param  array        $context      Optional FK references (s3_account_id, s3_bucket_id, etc.) and a 'reason'
     */
    public static function log(string $action, ?string $performedBy, array $context = []): void
    {
        $data = [
            'action'       => $action,
            'performed_by' => $performedBy,
            // Allow callers to pass the agent's original timestamp via context['performed_at'].
            'performed_at' => $context['performed_at'] ?? now(),
        ];

        // Map recognized context keys directly onto their DB columns.
        // iam_account_id and iam_user_id must be mapped explicitly — they must
        // not fall through to the JSON data column.
        $fillable = [
            'iam_account_id', 'iam_user_id',
            's3_account_id', 's3_server_id', 's3_access_key_id',
            's3_bucket_id', 's3_worm_commitment_id', 'reason',
        ];

        foreach ($fillable as $key) {
            if (array_key_exists($key, $context)) {
                $data[$key] = $context[$key];
            }
        }

        // Everything else goes into the JSON data column (e.g. object_key, size_bytes).
        $skip  = array_merge($fillable, ['performed_at']);
        $extra = array_diff_key($context, array_flip($skip));
        if (!empty($extra)) {
            $data['data'] = $extra;
        }

        // Writing an audit log is a system-triggered side effect of an already-authorized
        // action (e.g. reveal/revoke), not something the caller is "creating" themselves.
        // Roles like s3-user/s3-manager only grant s3_audit_logs:read, so without bypassing
        // the create-authorization check here, AuditLogsObserver's creating() check would
        // throw NotAllowedException and abort the whole request.
        \NextDeveloper\IAM\Helpers\UserHelper::withRolesCheckBypassed(
            fn () => \NextDeveloper\S3\Database\Models\AuditLogs::create($data)
        );
    }
}