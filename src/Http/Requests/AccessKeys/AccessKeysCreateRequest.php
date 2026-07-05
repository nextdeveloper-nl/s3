<?php

namespace NextDeveloper\S3\Http\Requests\AccessKeys;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class AccessKeysCreateRequest extends AbstractFormRequest
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * @return array
     */
    public function rules()
    {
        return [
            's3_account_id'  => 'required|exists:s3_accounts,uuid|uuid',
            // Customer-facing label, also sent to the storage agent as the IAM
            // identity's name/user_id — previously always blank (see AccessKeysService::create()).
            'name'           => 'nullable|string',
            // Accepts both agent names (readwrite/readonly/writeonly/nodelete) and
            // friendly aliases (full_access/read_only/write_only/no_delete).
            // Normalized to agent names in AccessKeysService::create().
            'role'           => 'required|string|in:readwrite,readonly,writeonly,nodelete,full_access,read_only,write_only,no_delete',
            'bucket_acls'    => 'nullable',
            'status'         => 'nullable|string',
            'expires_at'     => 'nullable|date',
            'revoked_reason' => 'nullable|string',
            'tags'           => 'nullable',
        ];
    }
}