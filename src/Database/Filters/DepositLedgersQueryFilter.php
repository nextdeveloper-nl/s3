<?php

namespace NextDeveloper\S3\Database\Filters;

use Illuminate\Database\Eloquent\Builder;
use NextDeveloper\Commons\Database\Filters\AbstractQueryFilter;
            

/**
 * This class automatically puts where clause on database so that use can filter
 * data returned from the query.
 */
class DepositLedgersQueryFilter extends AbstractQueryFilter
{

    /**
     * @var Builder
     */
    protected $builder;
    
    public function type($value)
    {
        return $this->builder->where('type', 'ilike', '%' . $value . '%');
    }

        
    public function reference($value)
    {
        return $this->builder->where('reference', 'ilike', '%' . $value . '%');
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
        
    public function notes($value)
    {
        return $this->builder->where('notes', 'ilike', '%' . $value . '%');
    }

    
    public function daysRemaining($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('days_remaining', $operator, $value);
    }

        //  This is an alias function of daysRemaining
    public function days_remaining($value)
    {
        return $this->daysRemaining($value);
    }
    
    public function daysTotal($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('days_total', $operator, $value);
    }

        //  This is an alias function of daysTotal
    public function days_total($value)
    {
        return $this->daysTotal($value);
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
    
    public function iamAccountId($value)
    {
            $iamAccount = \NextDeveloper\IAM\Database\Models\Accounts::where('uuid', $value)->first();

        if($iamAccount) {
            return $this->builder->where('iam_account_id', '=', $iamAccount->id);
        }
    }

    
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
