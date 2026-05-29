<?php

namespace NextDeveloper\S3\Http\Requests\NotificationsSents;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class NotificationsSentsCreateRequest extends AbstractFormRequest
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            's3_account_id' => 'required|exists:s3_accounts,uuid|uuid',
        'notification' => 'required|string',
        'month' => 'required|date',
        'sent_at' => 'date',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}