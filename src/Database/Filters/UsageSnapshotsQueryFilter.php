<?php

namespace NextDeveloper\S3\Database\Filters;

use Illuminate\Database\Eloquent\Builder;
use NextDeveloper\Commons\Database\Filters\AbstractQueryFilter;
        

/**
 * This class automatically puts where clause on database so that use can filter
 * data returned from the query.
 */
class UsageSnapshotsQueryFilter extends AbstractQueryFilter
{

    /**
     * @var Builder
     */
    protected $builder;

    public function storageBytes($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('storage_bytes', $operator, $value);
    }

        //  This is an alias function of storageBytes
    public function storage_bytes($value)
    {
        return $this->storageBytes($value);
    }

    public function objectCount($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('object_count', $operator, $value);
    }

        //  This is an alias function of objectCount
    public function object_count($value)
    {
        return $this->objectCount($value);
    }

    public function snapshotAtStart($date)
    {
        return $this->builder->where('snapshot_at', '>=', $date);
    }

    public function snapshotAtEnd($date)
    {
        return $this->builder->where('snapshot_at', '<=', $date);
    }

    //  This is an alias function of snapshotAt
    public function snapshot_at_start($value)
    {
        return $this->snapshotAtStart($value);
    }

    //  This is an alias function of snapshotAt
    public function snapshot_at_end($value)
    {
        return $this->snapshotAtEnd($value);
    }

    public function s3AccountId($value)
    {
            $s3Account = \NextDeveloper\S3\Database\Models\Accounts::where('uuid', $value)->first();

        if($s3Account) {
            return $this->builder->where('s3_account_id', '=', $s3Account->id);
        }
    }

        //  This is an alias function of s3Account
    public function s3_account_id($value)
    {
        return $this->s3Account($value);
    }

    public function iamAccountId($value)
    {
            $iamAccount = \NextDeveloper\IAM\Database\Models\Accounts::where('uuid', $value)->first();

        if($iamAccount) {
            return $this->builder->where('iam_account_id', '=', $iamAccount->id);
        }
    }


    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
