<?php

namespace NextDeveloper\S3\Http\Requests\AccountStats;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class AccountStatsCreateRequest extends AbstractFormRequest
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            's3_account_id' => 'nullable|exists:s3_accounts,uuid|uuid',
        'slug' => 'nullable|string',
        'status' => 'nullable|string',
        'blocked_at' => 'nullable|date',
        'blocked_reason' => 'nullable|string',
        'usage_checked_at' => 'nullable|date',
        'quota_storage_bytes' => 'nullable|integer',
        'storage_bytes_used' => 'nullable|integer',
        'storage_pct' => 'nullable',
        'quota_egress_bytes_mo' => 'nullable|integer',
        'egress_bytes_mo_used' => 'nullable|integer',
        'egress_pct' => 'nullable',
        'quota_max_objects' => 'nullable|integer',
        'object_count' => 'nullable|integer',
        'object_pct' => 'nullable',
        'quota_max_buckets' => 'nullable|integer',
        'bucket_count' => 'nullable|integer',
        'bucket_pct' => 'nullable',
        'active_key_count' => 'nullable|integer',
        'in_progress_upload_count' => 'nullable|integer',
        'paused_webhook_count' => 'nullable|integer',
        'current_month_egress_bytes' => 'nullable|integer',
        'current_month_ingress_bytes' => 'nullable|integer',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}