<?php

namespace NextDeveloper\S3\Http\Requests\AccessKeys;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class AccessKeysCreateRequest extends AbstractFormRequest
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            's3_account_id' => 'required|exists:s3_accounts,uuid|uuid',
        'role' => 'string',
        'bucket_acls' => '',
        'status' => 'string',
        'expires_at' => 'nullable|date',
        'revoked_reason' => 'nullable|string',
        'tags' => '',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * @return array
     */
    public function rules()
    {
        return [
            's3_account_id'  => 'required|exists:s3_accounts,uuid|uuid',
            'role'           => 'required|string|in:readwrite,readonly,writeonly,nodelete',
            'bucket_acls'    => 'nullable',
            'status'         => 'nullable|string',
            'expires_at'     => 'nullable|date',
            'revoked_reason' => 'nullable|string',
            'tags'           => 'nullable',
        ];
    }
}