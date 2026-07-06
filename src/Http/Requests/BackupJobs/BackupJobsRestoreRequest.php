<?php

namespace NextDeveloper\S3\Http\Requests\BackupJobs;

use Illuminate\Contracts\Validation\Validator;
use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;
use NextDeveloper\S3\Database\Models\BackupJobs;

class BackupJobsRestoreRequest extends AbstractFormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            // Never defaults to the original source_paths — restoring over the
            // original location is allowed, but only if explicitly requested.
            'destination_path'      => 'required|string',
            // Relative paths within the backup to restore. Omitted/empty
            // restores everything — expected to be the less common case.
            'restore_paths'         => 'nullable|array',
            'restore_paths.*'       => 'string',
            // Required for engine=kopia jobs (picks the snapshot to restore
            // from); must be absent for engine=rsync jobs — see withValidator().
            's3_backup_job_run_id'  => 'nullable|exists:s3_backup_job_runs,uuid|uuid',
        ];
    }

    /**
     * @param Validator $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function (Validator $validator) {
            $job = BackupJobs::where('uuid', $this->route('s3_backup_jobs'))->first();

            if (!$job) {
                return;
            }

            if ($job->engine === 'kopia' && !$this->input('s3_backup_job_run_id')) {
                $validator->errors()->add(
                    's3_backup_job_run_id',
                    'This job uses the kopia engine — s3_backup_job_run_id is required to pick which snapshot to restore.'
                );
            }

            if ($job->engine === 'rsync' && $this->input('s3_backup_job_run_id')) {
                $validator->errors()->add(
                    's3_backup_job_run_id',
                    'This job uses the rsync engine — it has no point-in-time snapshot, so s3_backup_job_run_id must not be set.'
                );
            }
        });
    }
}
