<?php

namespace NextDeveloper\S3\Http\Requests\WormCommitments;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class WormCommitmentsUpdateRequest extends AbstractFormRequest
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            's3_bucket_id' => 'nullable|exists:s3_buckets,uuid|uuid',
        's3_account_id' => 'nullable|exists:s3_accounts,uuid|uuid',
        'mode' => 'nullable|string',
        'retention_days' => 'nullable|integer',
        'quota_bytes' => 'nullable|integer',
        'status' => 'string',
        'cancelled_at' => 'nullable|date',
        'expired_at' => 'nullable|date',
        'purged_at' => 'nullable|date',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}