<?php

namespace NextDeveloper\S3\Http\Requests\AccessKeysPerspective;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class AccessKeysPerspectiveCreateRequest extends AbstractFormRequest
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            's3_account_id' => 'nullable|exists:s3_accounts,uuid|uuid',
        'access_key' => 'nullable|string',
        'role' => 'nullable|string',
        'bucket_acls' => 'nullable',
        'status' => 'nullable|string',
        'expires_at' => 'nullable|date',
        'last_used_at' => 'nullable|date',
        'revoked_at' => 'nullable|date',
        'revoked_reason' => 'nullable|string',
        'tags' => 'nullable',
        's3_account_slug' => 'nullable|string',
        'is_expired' => 'nullable|boolean',
        'expires_in_days' => 'nullable|integer',
        'is_expiring_soon' => 'nullable|boolean',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}