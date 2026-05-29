<?php

namespace NextDeveloper\S3\Http\Requests\WormCommitments;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class WormCommitmentsCreateRequest extends AbstractFormRequest
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            's3_bucket_id' => 'required|exists:s3_buckets,uuid|uuid',
        's3_account_id' => 'required|exists:s3_accounts,uuid|uuid',
        'mode' => 'required|string',
        'retention_days' => 'required|integer',
        'quota_bytes' => 'required|integer',
        'status' => 'string',
        'cancelled_at' => 'nullable|date',
        'expired_at' => 'nullable|date',
        'purged_at' => 'nullable|date',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * @return array
     */
    public function rules()
    {
        return [
            's3_bucket_id'  => 'required|exists:s3_buckets,uuid|uuid',
            's3_account_id' => 'required|exists:s3_accounts,uuid|uuid',
            'mode'          => 'required|string|in:GOVERNANCE,COMPLIANCE',
            'retention_days' => 'required|integer|min:1',
            'quota_bytes'   => 'required|integer|min:1',
            'price_per_gb_mo' => 'nullable|numeric|min:0',
        ];
    }
}