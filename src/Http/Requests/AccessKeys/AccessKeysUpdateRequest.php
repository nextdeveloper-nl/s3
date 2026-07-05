<?php

namespace NextDeveloper\S3\Http\Requests\AccessKeys;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class AccessKeysUpdateRequest extends AbstractFormRequest
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            's3_account_id' => 'nullable|exists:s3_accounts,uuid|uuid',
        'name' => 'nullable|string',
        'role' => 'string',
        'bucket_acls' => '',
        'status' => 'string',
        'expires_at' => 'nullable|date',
        'revoked_reason' => 'nullable|string',
        'tags' => '',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}