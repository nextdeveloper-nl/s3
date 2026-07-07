<?php

namespace NextDeveloper\S3\Http\Requests\QuotaAlertsPerspective;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class QuotaAlertsPerspectiveCreateRequest extends AbstractFormRequest
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            's3_account_id' => 'nullable|exists:s3_accounts,uuid|uuid',
            's3_account_slug' => 'nullable|string',
            'quota_storage_bytes' => 'nullable|integer',
            'storage_bytes_used' => 'nullable|integer',
            'quota_egress_bytes_mo' => 'nullable|integer',
            'egress_bytes_mo_used' => 'nullable|integer',
            'severity' => 'nullable|string',
            'is_blocked' => 'nullable|boolean',
            'blocked_at' => 'nullable|date',
            'blocked_reason' => 'nullable|string',
            'usage_checked_at' => 'nullable|date',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
