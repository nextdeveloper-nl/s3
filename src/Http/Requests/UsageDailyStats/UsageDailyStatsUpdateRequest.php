<?php

namespace NextDeveloper\S3\Http\Requests\UsageDailyStats;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class UsageDailyStatsUpdateRequest extends AbstractFormRequest
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'stat_date' => 'nullable|date',
        's3_account_id' => 'nullable|exists:s3_accounts,uuid|uuid',
        'slug' => 'nullable|string',
        'storage_bytes' => 'nullable|integer',
        'object_count' => 'nullable|integer',
        'storage_gb' => 'nullable',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}