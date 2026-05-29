<?php

namespace NextDeveloper\S3\Http\Requests\AuditLogs;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class AuditLogsCreateRequest extends AbstractFormRequest
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            's3_account_id' => 'nullable|exists:s3_accounts,uuid|uuid',
        's3_server_id' => 'nullable|exists:s3_servers,uuid|uuid',
        's3_access_key_id' => 'nullable|exists:s3_access_keys,uuid|uuid',
        's3_bucket_id' => 'nullable|exists:s3_buckets,uuid|uuid',
        's3_worm_commitment_id' => 'nullable|exists:s3_worm_commitments,uuid|uuid',
        'action' => 'required|string',
        'performed_by' => 'required|string',
        'reason' => 'nullable|string',
        'data' => 'nullable',
        'performed_at' => 'date',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}