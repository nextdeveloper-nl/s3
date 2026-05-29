<?php

namespace NextDeveloper\S3\Http\Requests\BandwidthMonthlies;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class BandwidthMonthliesCreateRequest extends AbstractFormRequest
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            's3_account_id' => 'required|exists:s3_accounts,uuid|uuid',
        'month' => 'required|date',
        'egress_bytes' => 'integer',
        'ingress_bytes' => 'integer',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}