<?php

namespace NextDeveloper\S3\Database\Filters;

use Illuminate\Database\Eloquent\Builder;
use NextDeveloper\Commons\Database\Filters\AbstractQueryFilter;
                

/**
 * This class automatically puts where clause on database so that use can filter
 * data returned from the query.
 */
class WormExpiringPerspectiveQueryFilter extends AbstractQueryFilter
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


    public function s3AccountSlug($value)
    {
        return $this->builder->where('s3_account_slug', 'ilike', '%' . $value . '%');
    }

        //  This is an alias function of s3AccountSlug
    public function s3_account_slug($value)
    {
        return $this->s3AccountSlug($value);
    }

    public function bucketName($value)
    {
        return $this->builder->where('bucket_name', 'ilike', '%' . $value . '%');
    }

        //  This is an alias function of bucketName
    public function bucket_name($value)
    {
        return $this->bucketName($value);
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

    public function daysUntilExpiry($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('days_until_expiry', $operator, $value);
    }

        //  This is an alias function of daysUntilExpiry
    public function days_until_expiry($value)
    {
        return $this->daysUntilExpiry($value);
    }

    public function isExpired($value)
    {
        return $this->builder->where('is_expired', $value);
    }

        //  This is an alias function of isExpired
    public function is_expired($value)
    {
        return $this->isExpired($value);
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

    public function s3WormCommitmentId($value)
    {
            $s3WormCommitment = \NextDeveloper\S3\Database\Models\WormCommitments::where('uuid', $value)->first();

        if($s3WormCommitment) {
            return $this->builder->where('s3_worm_commitment_id', '=', $s3WormCommitment->id);
        }
    }

        //  This is an alias function of s3WormCommitment
    public function s3_worm_commitment_id($value)
    {
        return $this->s3WormCommitment($value);
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
