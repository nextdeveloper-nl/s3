<?php

namespace NextDeveloper\S3\Http\Requests\Buckets;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class BucketsUpdateRequest extends AbstractFormRequest
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
        'replication_factor' => 'integer',
        'lifecycle_rules' => '',
        'versioning' => 'string',
        'mfa_delete' => 'boolean',
        'status' => 'string',
        'tags' => '',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}