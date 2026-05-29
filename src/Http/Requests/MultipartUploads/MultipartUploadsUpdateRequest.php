<?php

namespace NextDeveloper\S3\Http\Requests\MultipartUploads;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class MultipartUploadsUpdateRequest extends AbstractFormRequest
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            's3_account_id' => 'nullable|exists:s3_accounts,uuid|uuid',
        's3_bucket_id' => 'nullable|exists:s3_buckets,uuid|uuid',
        'upload_id' => 'nullable|string|exists:uploads,uuid|uuid',
        'object_key' => 'nullable|string',
        'initiated_at' => 'nullable|date',
        'status' => 'string',
        'size_bytes_so_far' => 'integer',
        'part_count' => 'integer',
        'last_activity_at' => 'nullable|date',
        'aborted_at' => 'nullable|date',
        'aborted_reason' => 'nullable|string',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}