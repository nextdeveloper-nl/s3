<?php

namespace NextDeveloper\S3\Http\Requests\UsageSnapshots;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class UsageSnapshotsUpdateRequest extends AbstractFormRequest
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            's3_account_id' => 'nullable|exists:s3_accounts,uuid|uuid',
        'snapshot_at' => 'date',
        'storage_bytes' => 'nullable|integer',
        'object_count' => 'nullable|integer',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}