<?php

namespace NextDeveloper\S3\Database\Filters;

use Illuminate\Database\Eloquent\Builder;
use NextDeveloper\Commons\Database\Filters\AbstractQueryFilter;
                            

/**
 * This class automatically puts where clause on database so that use can filter
 * data returned from the query.
 */
class AuditLogsQueryFilter extends AbstractQueryFilter
{

    /**
     * @var Builder
     */
    protected $builder;
    
    public function action($value)
    {
        return $this->builder->where('action', 'ilike', '%' . $value . '%');
    }

        
    public function performedBy($value)
    {
        return $this->builder->where('performed_by', 'ilike', '%' . $value . '%');
    }

        //  This is an alias function of performedBy
    public function performed_by($value)
    {
        return $this->performedBy($value);
    }
        
    public function reason($value)
    {
        return $this->builder->where('reason', 'ilike', '%' . $value . '%');
    }

    
    public function performedAtStart($date)
    {
        return $this->builder->where('performed_at', '>=', $date);
    }

    public function performedAtEnd($date)
    {
        return $this->builder->where('performed_at', '<=', $date);
    }

    //  This is an alias function of performedAt
    public function performed_at_start($value)
    {
        return $this->performedAtStart($value);
    }

    //  This is an alias function of performedAt
    public function performed_at_end($value)
    {
        return $this->performedAtEnd($value);
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
    
    public function s3ServerId($value)
    {
            $s3Server = \NextDeveloper\S3\Database\Models\Servers::where('uuid', $value)->first();

        if($s3Server) {
            return $this->builder->where('s3_server_id', '=', $s3Server->id);
        }
    }

        //  This is an alias function of s3Server
    public function s3_server_id($value)
    {
        return $this->s3Server($value);
    }
    
    public function s3AccessKeyId($value)
    {
            $s3AccessKey = \NextDeveloper\S3\Database\Models\AccessKeys::where('uuid', $value)->first();

        if($s3AccessKey) {
            return $this->builder->where('s3_access_key_id', '=', $s3AccessKey->id);
        }
    }

        //  This is an alias function of s3AccessKey
    public function s3_access_key_id($value)
    {
        return $this->s3AccessKey($value);
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
    
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
