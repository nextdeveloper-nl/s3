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
            's3_account_id'      => 'nullable|exists:s3_accounts,uuid|uuid',
            's3_server_id'       => 'nullable|exists:s3_servers,uuid|uuid',
            'name'               => 'nullable|string',
            // bucket_name is immutable after creation and will be stripped in BucketsService
            'bucket_name'        => 'nullable|string',
            'replication_factor' => 'nullable|integer',
            'lifecycle_rules'    => 'nullable',
            'versioning'         => 'nullable|string|in:Suspended,Enabled',
            'mfa_delete'         => 'nullable|boolean',
            'status'             => 'nullable|string',
            'tags'               => 'nullable',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}