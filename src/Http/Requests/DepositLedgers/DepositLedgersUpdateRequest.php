<?php

namespace NextDeveloper\S3\Http\Requests\DepositLedgers;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class DepositLedgersUpdateRequest extends AbstractFormRequest
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            's3_account_id' => 'nullable|exists:s3_accounts,uuid|uuid',
        's3_worm_commitment_id' => 'nullable|exists:s3_worm_commitments,uuid|uuid',
        'type' => 'nullable|string',
        'amount' => 'nullable',
        'days_remaining' => 'nullable|integer',
        'days_total' => 'nullable|integer',
        'reference' => 'nullable|string',
        'performed_by' => 'nullable|string',
        'notes' => 'nullable|string',
        'performed_at' => 'date',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}