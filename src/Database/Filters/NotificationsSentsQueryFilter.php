<?php

namespace NextDeveloper\S3\Database\Filters;

use Illuminate\Database\Eloquent\Builder;
use NextDeveloper\Commons\Database\Filters\AbstractQueryFilter;
        

/**
 * This class automatically puts where clause on database so that use can filter
 * data returned from the query.
 */
class NotificationsSentsQueryFilter extends AbstractQueryFilter
{

    /**
     * @var Builder
     */
    protected $builder;

    public function notification($value)
    {
        return $this->builder->where('notification', 'ilike', '%' . $value . '%');
    }


    public function monthStart($date)
    {
        return $this->builder->where('month', '>=', $date);
    }

    public function monthEnd($date)
    {
        return $this->builder->where('month', '<=', $date);
    }

    //  This is an alias function of month
    public function month_start($value)
    {
        return $this->monthStart($value);
    }

    //  This is an alias function of month
    public function month_end($value)
    {
        return $this->monthEnd($value);
    }

    public function sentAtStart($date)
    {
        return $this->builder->where('sent_at', '>=', $date);
    }

    public function sentAtEnd($date)
    {
        return $this->builder->where('sent_at', '<=', $date);
    }

    //  This is an alias function of sentAt
    public function sent_at_start($value)
    {
        return $this->sentAtStart($value);
    }

    //  This is an alias function of sentAt
    public function sent_at_end($value)
    {
        return $this->sentAtEnd($value);
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
