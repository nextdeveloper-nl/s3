<?php

namespace NextDeveloper\S3\Http\Requests\BucketsPerspective;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class BucketsPerspectiveUpdateRequest extends AbstractFormRequest
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            's3_account_id' => 'nullable|exists:s3_accounts,uuid|uuid',
        's3_server_id' => 'nullable|exists:s3_servers,uuid|uuid',
        'name' => 'nullable|string',
        'replication_factor' => 'nullable|integer',
        'lifecycle_rules' => 'nullable',
        'versioning' => 'nullable|string',
        'mfa_delete' => 'nullable|boolean',
        'object_lock_enabled' => 'nullable|boolean',
        'object_lock_mode' => 'nullable|string',
        'object_lock_days' => 'nullable|integer',
        'object_count' => 'nullable|integer',
        'size_bytes' => 'nullable|integer',
        'size_gb' => 'nullable',
        'replica_health' => 'nullable|string',
        'status' => 'nullable|string',
        'tags' => 'nullable',
        's3_account_slug' => 'nullable|string',
        's3_server_hostname' => 'nullable|string',
        's3_server_health' => 'nullable|string',
        'worm_status' => 'nullable|string',
        'worm_locks_until' => 'nullable|date',
        'active_webhook_count' => 'nullable|integer',
        'in_progress_uploads' => 'nullable|integer',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}