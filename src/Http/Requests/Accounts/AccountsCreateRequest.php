<?php

namespace NextDeveloper\S3\Http\Requests\Accounts;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class AccountsCreateRequest extends AbstractFormRequest
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'slug' => 'required|string',
        'status' => 'string',
        'quota_storage_bytes' => 'integer',
        'quota_egress_bytes_mo' => 'integer',
        'quota_max_buckets' => 'integer',
        'quota_max_objects' => 'integer',
        'tags' => '',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}