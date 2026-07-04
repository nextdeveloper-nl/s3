<?php

namespace NextDeveloper\S3\Database\Filters;

use Illuminate\Database\Eloquent\Builder;
use NextDeveloper\Commons\Database\Filters\AbstractQueryFilter;

/**
 * This class automatically puts where clause on database so that use can filter
 * data returned from the query.
 */
class BackupJobsQueryFilter extends AbstractQueryFilter
{
    /**
     * @var Builder
     */
    protected $builder;

    public function tags($values)
    {
        $tags = explode(',', $values);

        $search = '';

        for($i = 0; $i < count($tags); $i++) {
            $search .= "'" . trim($tags[$i]) . "',";
        }

        $search = substr($search, 0, -1);

        return $this->builder->whereRaw('tags @> ARRAY[' . $search . ']');
    }

    public function name($value)
    {
        return $this->builder->where('name', 'ilike', '%' . $value . '%');
    }

    public function jobType($value)
    {
        return $this->builder->where('job_type', '=', $value);
    }

    //  This is an alias function of jobType
    public function job_type($value)
    {
        return $this->jobType($value);
    }

    public function isEnabled($value)
    {
        return $this->builder->where('is_enabled', '=', filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }

    //  This is an alias function of isEnabled
    public function is_enabled($value)
    {
        return $this->isEnabled($value);
    }

    public function status($value)
    {
        return $this->builder->where('status', '=', $value);
    }

    public function s3BackupAgentId($value)
    {
        $agent = \NextDeveloper\S3\Database\Models\BackupAgents::where('uuid', $value)->first();

        if($agent) {
            return $this->builder->where('s3_backup_agent_id', '=', $agent->id);
        }
    }

    //  This is an alias function of s3BackupAgentId
    public function s3_backup_agent_id($value)
    {
        return $this->s3BackupAgentId($value);
    }

    public function s3BucketId($value)
    {
        $bucket = \NextDeveloper\S3\Database\Models\Buckets::where('uuid', $value)->first();

        if($bucket) {
            return $this->builder->where('s3_bucket_id', '=', $bucket->id);
        }
    }

    //  This is an alias function of s3BucketId
    public function s3_bucket_id($value)
    {
        return $this->s3BucketId($value);
    }

    public function iamAccountId($value)
    {
        $iamAccount = \NextDeveloper\IAM\Database\Models\Accounts::where('uuid', $value)->first();

        if($iamAccount) {
            return $this->builder->where('iam_account_id', '=', $iamAccount->id);
        }
    }

    public function iamUserId($value)
    {
        $iamUser = \NextDeveloper\IAM\Database\Models\Users::where('uuid', $value)->first();

        if($iamUser) {
            return $this->builder->where('iam_user_id', '=', $iamUser->id);
        }
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

    public function updatedAtStart($date)
    {
        return $this->builder->where('updated_at', '>=', $date);
    }

    public function updatedAtEnd($date)
    {
        return $this->builder->where('updated_at', '<=', $date);
    }

    public function updated_at_start($value)
    {
        return $this->updatedAtStart($value);
    }

    public function updated_at_end($value)
    {
        return $this->updatedAtEnd($value);
    }

    public function deletedAtStart($date)
    {
        return $this->builder->where('deleted_at', '>=', $date);
    }

    public function deletedAtEnd($date)
    {
        return $this->builder->where('deleted_at', '<=', $date);
    }

    public function deleted_at_start($value)
    {
        return $this->deletedAtStart($value);
    }

    public function deleted_at_end($value)
    {
        return $this->deletedAtEnd($value);
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
