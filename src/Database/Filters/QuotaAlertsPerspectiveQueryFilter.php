<?php

namespace NextDeveloper\S3\Database\Filters;

use Illuminate\Database\Eloquent\Builder;
use NextDeveloper\Commons\Database\Filters\AbstractQueryFilter;


/**
 * This class automatically puts where clause on database so that use can filter
 * data returned from the query.
 */
class QuotaAlertsPerspectiveQueryFilter extends AbstractQueryFilter
{

    /**
     * @var Builder
     */
    protected $builder;

    public function s3AccountSlug($value)
    {
        return $this->builder->where('s3_account_slug', 'ilike', '%' . $value . '%');
    }

        //  This is an alias function of s3AccountSlug
    public function s3_account_slug($value)
    {
        return $this->s3AccountSlug($value);
    }

    public function severity($value)
    {
        return $this->builder->where('severity', 'ilike', '%' . $value . '%');
    }

    public function isBlocked($value)
    {
        return $this->builder->where('is_blocked', $value);
    }

        //  This is an alias function of isBlocked
    public function is_blocked($value)
    {
        return $this->isBlocked($value);
    }

    public function storageUsagePct($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('storage_usage_pct', $operator, $value);
    }

        //  This is an alias function of storageUsagePct
    public function storage_usage_pct($value)
    {
        return $this->storageUsagePct($value);
    }

    public function egressUsagePct($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('egress_usage_pct', $operator, $value);
    }

        //  This is an alias function of egressUsagePct
    public function egress_usage_pct($value)
    {
        return $this->egressUsagePct($value);
    }

    public function blockedAtStart($date)
    {
        return $this->builder->where('blocked_at', '>=', $date);
    }

    public function blockedAtEnd($date)
    {
        return $this->builder->where('blocked_at', '<=', $date);
    }

    //  This is an alias function of blockedAt
    public function blocked_at_start($value)
    {
        return $this->blockedAtStart($value);
    }

    //  This is an alias function of blockedAt
    public function blocked_at_end($value)
    {
        return $this->blockedAtEnd($value);
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
        return $this->s3AccountId($value);
    }

    public function iamAccountId($value)
    {
            $iamAccount = \NextDeveloper\IAM\Database\Models\Accounts::where('uuid', $value)->first();

        if($iamAccount) {
            return $this->builder->where('iam_account_id', '=', $iamAccount->id);
        }
    }

        //  This is an alias function of iamAccount
    public function iam_account_id($value)
    {
        return $this->iamAccountId($value);
    }


    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
