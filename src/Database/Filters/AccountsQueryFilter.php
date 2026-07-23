<?php

namespace NextDeveloper\S3\Database\Filters;

use Illuminate\Database\Eloquent\Builder;
use NextDeveloper\Commons\Database\Filters\AbstractQueryFilter;
        

/**
 * This class automatically puts where clause on database so that use can filter
 * data returned from the query.
 */
class AccountsQueryFilter extends AbstractQueryFilter
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

    public function slug($value)
    {
        return $this->builder->where('slug', 'ilike', '%' . $value . '%');
    }


    public function status($value)
    {
        return $this->builder->where('status', 'ilike', '%' . $value . '%');
    }


    public function blockedReason($value)
    {
        return $this->builder->where('blocked_reason', 'ilike', '%' . $value . '%');
    }

        //  This is an alias function of blockedReason
    public function blocked_reason($value)
    {
        return $this->blockedReason($value);
    }

    public function quotaStorageBytes($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('quota_storage_bytes', $operator, $value);
    }

        //  This is an alias function of quotaStorageBytes
    public function quota_storage_bytes($value)
    {
        return $this->quotaStorageBytes($value);
    }

    public function quotaEgressBytesMo($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('quota_egress_bytes_mo', $operator, $value);
    }

        //  This is an alias function of quotaEgressBytesMo
    public function quota_egress_bytes_mo($value)
    {
        return $this->quotaEgressBytesMo($value);
    }

    public function quotaMaxBuckets($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('quota_max_buckets', $operator, $value);
    }

        //  This is an alias function of quotaMaxBuckets
    public function quota_max_buckets($value)
    {
        return $this->quotaMaxBuckets($value);
    }

    public function quotaMaxObjects($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('quota_max_objects', $operator, $value);
    }

        //  This is an alias function of quotaMaxObjects
    public function quota_max_objects($value)
    {
        return $this->quotaMaxObjects($value);
    }

    public function storageBytesUsed($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('storage_bytes_used', $operator, $value);
    }

        //  This is an alias function of storageBytesUsed
    public function storage_bytes_used($value)
    {
        return $this->storageBytesUsed($value);
    }

    public function egressBytesMoUsed($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('egress_bytes_mo_used', $operator, $value);
    }

        //  This is an alias function of egressBytesMoUsed
    public function egress_bytes_mo_used($value)
    {
        return $this->egressBytesMoUsed($value);
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

    public function usageCheckedAtStart($date)
    {
        return $this->builder->where('usage_checked_at', '>=', $date);
    }

    public function usageCheckedAtEnd($date)
    {
        return $this->builder->where('usage_checked_at', '<=', $date);
    }

    //  This is an alias function of usageCheckedAt
    public function usage_checked_at_start($value)
    {
        return $this->usageCheckedAtStart($value);
    }

    //  This is an alias function of usageCheckedAt
    public function usage_checked_at_end($value)
    {
        return $this->usageCheckedAtEnd($value);
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
