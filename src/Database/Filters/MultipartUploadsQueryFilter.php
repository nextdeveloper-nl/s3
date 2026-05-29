<?php

namespace NextDeveloper\S3\Database\Filters;

use Illuminate\Database\Eloquent\Builder;
use NextDeveloper\Commons\Database\Filters\AbstractQueryFilter;
                

/**
 * This class automatically puts where clause on database so that use can filter
 * data returned from the query.
 */
class MultipartUploadsQueryFilter extends AbstractQueryFilter
{

    /**
     * @var Builder
     */
    protected $builder;
    
    public function uploadId($value)
    {
        return $this->builder->where('upload_id', 'ilike', '%' . $value . '%');
    }

        //  This is an alias function of uploadId
    public function upload_id($value)
    {
        return $this->uploadId($value);
    }
        
    public function objectKey($value)
    {
        return $this->builder->where('object_key', 'ilike', '%' . $value . '%');
    }

        //  This is an alias function of objectKey
    public function object_key($value)
    {
        return $this->objectKey($value);
    }
        
    public function status($value)
    {
        return $this->builder->where('status', 'ilike', '%' . $value . '%');
    }

        
    public function abortedReason($value)
    {
        return $this->builder->where('aborted_reason', 'ilike', '%' . $value . '%');
    }

        //  This is an alias function of abortedReason
    public function aborted_reason($value)
    {
        return $this->abortedReason($value);
    }
    
    public function sizeBytesSoFar($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('size_bytes_so_far', $operator, $value);
    }

        //  This is an alias function of sizeBytesSoFar
    public function size_bytes_so_far($value)
    {
        return $this->sizeBytesSoFar($value);
    }
    
    public function partCount($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('part_count', $operator, $value);
    }

        //  This is an alias function of partCount
    public function part_count($value)
    {
        return $this->partCount($value);
    }
    
    public function initiatedAtStart($date)
    {
        return $this->builder->where('initiated_at', '>=', $date);
    }

    public function initiatedAtEnd($date)
    {
        return $this->builder->where('initiated_at', '<=', $date);
    }

    //  This is an alias function of initiatedAt
    public function initiated_at_start($value)
    {
        return $this->initiatedAtStart($value);
    }

    //  This is an alias function of initiatedAt
    public function initiated_at_end($value)
    {
        return $this->initiatedAtEnd($value);
    }

    public function lastActivityAtStart($date)
    {
        return $this->builder->where('last_activity_at', '>=', $date);
    }

    public function lastActivityAtEnd($date)
    {
        return $this->builder->where('last_activity_at', '<=', $date);
    }

    //  This is an alias function of lastActivityAt
    public function last_activity_at_start($value)
    {
        return $this->lastActivityAtStart($value);
    }

    //  This is an alias function of lastActivityAt
    public function last_activity_at_end($value)
    {
        return $this->lastActivityAtEnd($value);
    }

    public function abortedAtStart($date)
    {
        return $this->builder->where('aborted_at', '>=', $date);
    }

    public function abortedAtEnd($date)
    {
        return $this->builder->where('aborted_at', '<=', $date);
    }

    //  This is an alias function of abortedAt
    public function aborted_at_start($value)
    {
        return $this->abortedAtStart($value);
    }

    //  This is an alias function of abortedAt
    public function aborted_at_end($value)
    {
        return $this->abortedAtEnd($value);
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
    
    public function iamAccountId($value)
    {
            $iamAccount = \NextDeveloper\IAM\Database\Models\Accounts::where('uuid', $value)->first();

        if($iamAccount) {
            return $this->builder->where('iam_account_id', '=', $iamAccount->id);
        }
    }

    
    public function uploadId($value)
    {
            $upload = \NextDeveloper\\Database\Models\Uploads::where('uuid', $value)->first();

        if($upload) {
            return $this->builder->where('upload_id', '=', $upload->id);
        }
    }

        //  This is an alias function of upload
    public function upload_id($value)
    {
        return $this->upload($value);
    }
    
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
