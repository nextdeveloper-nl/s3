<?php

namespace NextDeveloper\S3\Database\Filters;

use Illuminate\Database\Eloquent\Builder;
use NextDeveloper\Commons\Database\Filters\AbstractQueryFilter;

/**
 * This class automatically puts where clause on database so that use can filter
 * data returned from the query.
 */
class RestoreJobsQueryFilter extends AbstractQueryFilter
{
    /**
     * @var Builder
     */
    protected $builder;

    public function status($value)
    {
        return $this->builder->where('status', '=', $value);
    }

    public function triggeredBy($value)
    {
        return $this->builder->where('triggered_by', '=', $value);
    }

    //  This is an alias function of triggeredBy
    public function triggered_by($value)
    {
        return $this->triggeredBy($value);
    }

    public function s3BackupJobId($value)
    {
        $job = \NextDeveloper\S3\Database\Models\BackupJobs::where('uuid', $value)->first();

        if($job) {
            return $this->builder->where('s3_backup_job_id', '=', $job->id);
        }
    }

    //  This is an alias function of s3BackupJobId
    public function s3_backup_job_id($value)
    {
        return $this->s3BackupJobId($value);
    }

    public function s3BackupJobRunId($value)
    {
        $run = \NextDeveloper\S3\Database\Models\BackupJobRuns::where('uuid', $value)->first();

        if($run) {
            return $this->builder->where('s3_backup_job_run_id', '=', $run->id);
        }
    }

    //  This is an alias function of s3BackupJobRunId
    public function s3_backup_job_run_id($value)
    {
        return $this->s3BackupJobRunId($value);
    }

    public function iamAccountId($value)
    {
        $account = \NextDeveloper\IAM\Database\Models\Accounts::where('uuid', $value)->first();

        if($account) {
            return $this->builder->where('iam_account_id', '=', $account->id);
        }
    }

    //  This is an alias function of iamAccountId
    public function iam_account_id($value)
    {
        return $this->iamAccountId($value);
    }

    public function createdAtStart($date)
    {
        return $this->builder->where('created_at', '>=', $date);
    }

    public function createdAtEnd($date)
    {
        return $this->builder->where('created_at', '<=', $date);
    }

    public function created_at_start($value)
    {
        return $this->createdAtStart($value);
    }

    public function created_at_end($value)
    {
        return $this->createdAtEnd($value);
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
