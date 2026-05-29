<?php

namespace NextDeveloper\S3\Database\Filters;

use Illuminate\Database\Eloquent\Builder;
use NextDeveloper\Commons\Database\Filters\AbstractQueryFilter;
            

/**
 * This class automatically puts where clause on database so that use can filter
 * data returned from the query.
 */
class AccessKeysPerspectiveQueryFilter extends AbstractQueryFilter
{
    /**
     * Filter by tags
     *
     * @param  $values
     * @return Builder
     */
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

    /**
     * @var Builder
     */
    protected $builder;
    
    public function accessKey($value)
    {
        return $this->builder->where('access_key', 'ilike', '%' . $value . '%');
    }

        //  This is an alias function of accessKey
    public function access_key($value)
    {
        return $this->accessKey($value);
    }
        
    public function role($value)
    {
        return $this->builder->where('role', 'ilike', '%' . $value . '%');
    }

        
    public function status($value)
    {
        return $this->builder->where('status', 'ilike', '%' . $value . '%');
    }

        
    public function revokedReason($value)
    {
        return $this->builder->where('revoked_reason', 'ilike', '%' . $value . '%');
    }

        //  This is an alias function of revokedReason
    public function revoked_reason($value)
    {
        return $this->revokedReason($value);
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
    
    public function expiresInDays($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('expires_in_days', $operator, $value);
    }

        //  This is an alias function of expiresInDays
    public function expires_in_days($value)
    {
        return $this->expiresInDays($value);
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
     
    public function isExpiringSoon($value)
    {
        return $this->builder->where('is_expiring_soon', $value);
    }

        //  This is an alias function of isExpiringSoon
    public function is_expiring_soon($value)
    {
        return $this->isExpiringSoon($value);
    }
     
    public function expiresAtStart($date)
    {
        return $this->builder->where('expires_at', '>=', $date);
    }

    public function expiresAtEnd($date)
    {
        return $this->builder->where('expires_at', '<=', $date);
    }

    //  This is an alias function of expiresAt
    public function expires_at_start($value)
    {
        return $this->expiresAtStart($value);
    }

    //  This is an alias function of expiresAt
    public function expires_at_end($value)
    {
        return $this->expiresAtEnd($value);
    }

    public function lastUsedAtStart($date)
    {
        return $this->builder->where('last_used_at', '>=', $date);
    }

    public function lastUsedAtEnd($date)
    {
        return $this->builder->where('last_used_at', '<=', $date);
    }

    //  This is an alias function of lastUsedAt
    public function last_used_at_start($value)
    {
        return $this->lastUsedAtStart($value);
    }

    //  This is an alias function of lastUsedAt
    public function last_used_at_end($value)
    {
        return $this->lastUsedAtEnd($value);
    }

    public function revokedAtStart($date)
    {
        return $this->builder->where('revoked_at', '>=', $date);
    }

    public function revokedAtEnd($date)
    {
        return $this->builder->where('revoked_at', '<=', $date);
    }

    //  This is an alias function of revokedAt
    public function revoked_at_start($value)
    {
        return $this->revokedAtStart($value);
    }

    //  This is an alias function of revokedAt
    public function revoked_at_end($value)
    {
        return $this->revokedAtEnd($value);
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

    public function deletedAtStart($date)
    {
        return $this->builder->where('deleted_at', '>=', $date);
    }

    public function deletedAtEnd($date)
    {
        return $this->builder->where('deleted_at', '<=', $date);
    }

    //  This is an alias function of deletedAt
    public function deleted_at_start($value)
    {
        return $this->deletedAtStart($value);
    }

    //  This is an alias function of deletedAt
    public function deleted_at_end($value)
    {
        return $this->deletedAtEnd($value);
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

    
    public function iamUserId($value)
    {
            $iamUser = \NextDeveloper\IAM\Database\Models\Users::where('uuid', $value)->first();

        if($iamUser) {
            return $this->builder->where('iam_user_id', '=', $iamUser->id);
        }
    }

    
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
