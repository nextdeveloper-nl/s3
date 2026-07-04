<?php

namespace NextDeveloper\S3\Http\Requests\BackupJobs;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class BackupJobsUpdateRequest extends AbstractFormRequest
{

    /**
     * s3_backup_agent_id, job_type and object_lock_enabled are immutable after
     * creation (a job doesn't change which machine it lives on or its WORM
     * commitment mid-life) and are stripped in BackupJobsService::update().
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name'                 => 'nullable|string',
            'source_paths'         => 'nullable|array',
            'source_paths.*'       => 'string',
            'pre_script'           => 'nullable|string',
            'script_timeout_s'     => 'nullable|integer|min:1',
            'schedule'             => 'nullable|string',
            'keep_last_n'          => 'nullable|integer|min:1',
            'keep_for_days'        => 'nullable|integer|min:1',
            'is_enabled'           => 'nullable|boolean',
            'bandwidth_limit_mbps' => 'nullable|integer|min:1',
            'status'               => 'nullable|string',
            'tags'                 => 'nullable',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
