<?php

namespace NextDeveloper\S3\Http\Requests\WebhookDeliveries;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class WebhookDeliveriesUpdateRequest extends AbstractFormRequest
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            's3_webhook_id' => 'nullable|exists:s3_webhooks,uuid|uuid',
        's3_account_id' => 'nullable|exists:s3_accounts,uuid|uuid',
        'event_type' => 'nullable|string',
        'object_key' => 'nullable|string',
        'payload' => 'nullable',
        'status_code' => 'nullable|integer',
        'attempt' => 'integer',
        'next_retry_at' => 'nullable|date',
        'delivered_at' => 'nullable|date',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}