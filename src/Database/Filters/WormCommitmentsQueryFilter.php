<?php

namespace NextDeveloper\S3\Database\Filters;

use Illuminate\Database\Eloquent\Builder;
use NextDeveloper\Commons\Database\Filters\AbstractQueryFilter;
            

/**
 * This class automatically puts where clause on database so that use can filter
 * data returned from the query.
 */
class WormCommitmentsQueryFilter extends AbstractQueryFilter
{

    /**
     * @var Builder
     */
    protected $builder;

    public function mode($value)
    {
        return $this->builder->where('mode', 'ilike', '%' . $value . '%');
    }


    public function status($value)
    {
        return $this->builder->where('status', 'ilike', '%' . $value . '%');
    }


    public function retentionDays($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('retention_days', $operator, $value);
    }

        //  This is an alias function of retentionDays
    public function retention_days($value)
    {
        return $this->retentionDays($value);
    }

    public function quotaBytes($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('quota_bytes', $operator, $value);
    }

        //  This is an alias function of quotaBytes
    public function quota_bytes($value)
    {
        return $this->quotaBytes($value);
    }

    public function committedAtStart($date)
    {
        return $this->builder->where('committed_at', '>=', $date);
    }

    public function committedAtEnd($date)
    {
        return $this->builder->where('committed_at', '<=', $date);
    }

    //  This is an alias function of committedAt
    public function committed_at_start($value)
    {
        return $this->committedAtStart($value);
    }

    //  This is an alias function of committedAt
    public function committed_at_end($value)
    {
        return $this->committedAtEnd($value);
    }

    public function locksUntilStart($date)
    {
        return $this->builder->where('locks_until', '>=', $date);
    }

    public function locksUntilEnd($date)
    {
        return $this->builder->where('locks_until', '<=', $date);
    }

    //  This is an alias function of locksUntil
    public function locks_until_start($value)
    {
        return $this->locksUntilStart($value);
    }

    //  This is an alias function of locksUntil
    public function locks_until_end($value)
    {
        return $this->locksUntilEnd($value);
    }

    public function cancelledAtStart($date)
    {
        return $this->builder->where('cancelled_at', '>=', $date);
    }

    public function cancelledAtEnd($date)
    {
        return $this->builder->where('cancelled_at', '<=', $date);
    }

    //  This is an alias function of cancelledAt
    public function cancelled_at_start($value)
    {
        return $this->cancelledAtStart($value);
    }

    //  This is an alias function of cancelledAt
    public function cancelled_at_end($value)
    {
        return $this->cancelledAtEnd($value);
    }

    public function expiredAtStart($date)
    {
        return $this->builder->where('expired_at', '>=', $date);
    }

    public function expiredAtEnd($date)
    {
        return $this->builder->where('expired_at', '<=', $date);
    }

    //  This is an alias function of expiredAt
    public function expired_at_start($value)
    {
        return $this->expiredAtStart($value);
    }

    //  This is an alias function of expiredAt
    public function expired_at_end($value)
    {
        return $this->expiredAtEnd($value);
    }

    public function purgedAtStart($date)
    {
        return $this->builder->where('purged_at', '>=', $date);
    }

    public function purgedAtEnd($date)
    {
        return $this->builder->where('purged_at', '<=', $date);
    }

    //  This is an alias function of purgedAt
    public function purged_at_start($value)
    {
        return $this->purgedAtStart($value);
    }

    //  This is an alias function of purgedAt
    public function purged_at_end($value)
    {
        return $this->purgedAtEnd($value);
    }

    public function createdAtStart($date)
    {
        return $this->builder->where('created_at', '>=', $date);
    }

    public function createdAtEnd($date)
    {
        return $this->builder->where('created_at', '<=', $date);
    }

    //  This is an alias function of createdAt
    public function created_at_start($value)
    {
        return $this->createdAtStart($value);
    }

    //  This is an alias function of createdAt
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

    //  This is an alias function of updatedAt
    public function updated_at_start($value)
    {
        return $this->updatedAtStart($value);
    }

    //  This is an alias function of updatedAt
    public function updated_at_end($value)
    {
        return $this->updatedAtEnd($value);
    }

    public function s3BucketId($value)
    {
            $s3Bucket = \NextDeveloper\S3\Database\Models\Buckets::where('uuid', $value)->first();

        if($s3Bucket) {
            return $this->builder->where('s3_bucket_id', '=', $s3Bucket->id);
        }
    }

        //  This is an alias function of s3Bucket
    public function s3_bucket_id($value)
    {
        return $this->s3Bucket($value);
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
