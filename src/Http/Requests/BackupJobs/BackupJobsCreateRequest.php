<?php

namespace NextDeveloper\S3\Http\Requests\BackupJobs;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class BackupJobsCreateRequest extends AbstractFormRequest
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * @return array
     */
    public function rules()
    {
        return [
            's3_backup_agent_id'   => 'required|exists:s3_backup_agents,uuid|uuid',
            // optional: defaults to the agent's own bucket, or a freshly-provisioned WORM
            // bucket when object_lock_enabled=true — see BackupJobsService::resolveBucketForJob()
            's3_bucket_id'         => 'nullable|exists:s3_buckets,uuid|uuid',
            'name'                 => 'required|string',
            'job_type'             => 'required|string|in:files,script',
            // files job: paths to snapshot. script job: paths the script is expected to write to.
            'source_paths'         => 'nullable|array',
            'source_paths.*'       => 'string',
            // required when job_type=script; validated against job_type in BackupJobsService
            'pre_script'           => 'nullable|string',
            'script_timeout_s'     => 'nullable|integer|min:1',
            // cron expression, evaluated agent-side — see docs/backup.agent/protocol.md
            'schedule'             => 'required|string',
            'keep_last_n'          => 'nullable|integer|min:1',
            'keep_for_days'        => 'nullable|integer|min:1',
            'is_enabled'           => 'nullable|boolean',
            'bandwidth_limit_mbps' => 'nullable|integer|min:1',
            // routes the target bucket through s3_worm_commitments — immutable once set, like Buckets.object_lock_enabled
            'object_lock_enabled'  => 'nullable|boolean',
            'status'               => 'nullable|string',
            'tags'                 => 'nullable',
        ];
    }
}
