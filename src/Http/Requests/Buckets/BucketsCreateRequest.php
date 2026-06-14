<?php

namespace NextDeveloper\S3\Http\Requests\Buckets;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class BucketsCreateRequest extends AbstractFormRequest
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * @return array
     */
    public function rules()
    {
        return [
            // s3_account_id is resolved automatically from the current IAM account in BucketsService
            's3_server_id'        => 'required|exists:s3_servers,uuid|uuid',
            'name'                => 'required|string',
            // bucket_name is the actual S3 identifier; auto-generated from name if omitted
            'bucket_name'         => ['nullable', 'string', 'regex:/^[a-z0-9][a-z0-9\-]{1,61}[a-z0-9]$/'],
            'replication_factor'  => 'nullable|integer|min:1|max:3',
            'lifecycle_rules'     => 'nullable',
            'versioning'          => 'nullable|string|in:Suspended,Enabled',
            'mfa_delete'          => 'nullable|boolean',
            // Object Lock fields — immutable once set; validated here on create only
            'object_lock_enabled' => 'nullable|boolean',
            'object_lock_mode'    => 'nullable|string|in:GOVERNANCE,COMPLIANCE',
            'object_lock_days'    => 'nullable|integer|min:1',
            'status'              => 'nullable|string',
            'tags'                => 'nullable',
        ];
    }
}
