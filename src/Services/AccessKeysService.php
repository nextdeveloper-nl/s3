<?php

namespace NextDeveloper\S3\Services;

use NextDeveloper\IAM\Helpers\UserHelper;
use NextDeveloper\S3\Helpers\S3KeyHelper;
use NextDeveloper\S3\Services\AbstractServices\AbstractAccessKeysService;

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
     * Generate a key pair and persist the access key + encrypted secret.
     *
     * The returned model has a transient `plain_secret` attribute set so the
     * caller can show it once to the user — it is never stored in plaintext.
     */
    public static function create(array $data)
    {
        $pair = S3KeyHelper::generate();

        $data['access_key']     = $pair['access_key'];
        $data['secret_key_enc'] = S3KeyHelper::encrypt($pair['secret_key']);
        $data['status']         = $data['status'] ?? 'active';

        $model = parent::create($data);

        // Attach plaintext secret as a transient attribute — visible only now
        $model->plain_secret = $pair['secret_key'];

        return $model;
    }

    /**
     * Revoke a key so it can no longer authenticate.
     */
    public static function revoke(string $uuid, string $reason = ''): \NextDeveloper\S3\Database\Models\AccessKeys
    {
        $model = \NextDeveloper\S3\Database\Models\AccessKeys::where('uuid', $uuid)->firstOrFail();

        $model->update([
            'status'         => 'revoked',
            'revoked_at'     => now(),
            'revoked_reason' => $reason,
        ]);

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
        $model = \NextDeveloper\S3\Database\Models\AccessKeys::where('uuid', $uuid)->firstOrFail();

        AuditLogsService::log('key.reveal', UserHelper::me()->uuid ?? 'system', [
            's3_account_id'    => $model->s3_account_id,
            's3_access_key_id' => $model->id,
        ]);

        return S3KeyHelper::decrypt($model->secret_key_enc);
    }
}