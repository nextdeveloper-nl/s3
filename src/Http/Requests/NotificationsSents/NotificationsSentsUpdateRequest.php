<?php

namespace NextDeveloper\S3\Http\Requests\NotificationsSents;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class NotificationsSentsUpdateRequest extends AbstractFormRequest
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            's3_account_id' => 'nullable|exists:s3_accounts,uuid|uuid',
        'notification' => 'nullable|string',
        'month' => 'nullable|date',
        'sent_at' => 'date',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}