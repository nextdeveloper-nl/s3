<?php

namespace NextDeveloper\S3\Http\Requests\Webhooks;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class WebhooksUpdateRequest extends AbstractFormRequest
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            's3_account_id' => 'nullable|exists:s3_accounts,uuid|uuid',
        's3_bucket_id' => 'nullable|exists:s3_buckets,uuid|uuid',
        'endpoint_url' => 'nullable|string',
        'events' => 'nullable',
        'status' => 'string',
        'tags' => '',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}