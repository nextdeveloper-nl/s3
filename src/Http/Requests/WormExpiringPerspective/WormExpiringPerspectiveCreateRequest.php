<?php

namespace NextDeveloper\S3\Http\Requests\WormExpiringPerspective;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class WormExpiringPerspectiveCreateRequest extends AbstractFormRequest
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            's3_worm_commitment_id' => 'nullable|exists:s3_worm_commitments,uuid|uuid',
        's3_bucket_id' => 'nullable|exists:s3_buckets,uuid|uuid',
        's3_account_id' => 'nullable|exists:s3_accounts,uuid|uuid',
        'mode' => 'nullable|string',
        'retention_days' => 'nullable|integer',
        'quota_bytes' => 'nullable|integer',
        'deposit_amount' => 'nullable',
        'price_per_gb_mo' => 'nullable',
        'committed_at' => 'nullable|date',
        'locks_until' => 'nullable|date',
        'status' => 'nullable|string',
        'cancelled_at' => 'nullable|date',
        's3_account_slug' => 'nullable|string',
        'bucket_name' => 'nullable|string',
        'days_until_expiry' => 'nullable|integer',
        'is_expired' => 'nullable|boolean',
        'deposit_refund_estimate' => 'nullable',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}