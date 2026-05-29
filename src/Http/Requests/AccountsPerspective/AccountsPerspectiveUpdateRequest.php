<?php

namespace NextDeveloper\S3\Http\Requests\AccountsPerspective;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class AccountsPerspectiveUpdateRequest extends AbstractFormRequest
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'slug' => 'nullable|string',
        'status' => 'nullable|string',
        'quota_storage_bytes' => 'nullable|integer',
        'quota_egress_bytes_mo' => 'nullable|integer',
        'quota_max_buckets' => 'nullable|integer',
        'quota_max_objects' => 'nullable|integer',
        'storage_bytes_used' => 'nullable|integer',
        'egress_bytes_mo_used' => 'nullable|integer',
        'object_count' => 'nullable|integer',
        'usage_checked_at' => 'nullable|date',
        'blocked_at' => 'nullable|date',
        'blocked_reason' => 'nullable|string',
        'tags' => 'nullable',
        'storage_pct' => 'nullable',
        'egress_pct' => 'nullable',
        'object_pct' => 'nullable',
        'bucket_count' => 'nullable|integer',
        'bucket_pct' => 'nullable',
        'active_key_count' => 'nullable|integer',
        'current_month_egress_bytes' => 'nullable|integer',
        'current_month_ingress_bytes' => 'nullable|integer',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}