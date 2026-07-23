<?php

namespace NextDeveloper\S3\Database\Filters;

use Illuminate\Database\Eloquent\Builder;
use NextDeveloper\Commons\Database\Filters\AbstractQueryFilter;
        

/**
 * This class automatically puts where clause on database so that use can filter
 * data returned from the query.
 */
class AccountStatsQueryFilter extends AbstractQueryFilter
{

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

    public function bucketCount($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('bucket_count', $operator, $value);
    }

        //  This is an alias function of bucketCount
    public function bucket_count($value)
    {
        return $this->bucketCount($value);
    }

    public function activeKeyCount($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('active_key_count', $operator, $value);
    }

        //  This is an alias function of activeKeyCount
    public function active_key_count($value)
    {
        return $this->activeKeyCount($value);
    }

    public function inProgressUploadCount($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('in_progress_upload_count', $operator, $value);
    }

        //  This is an alias function of inProgressUploadCount
    public function in_progress_upload_count($value)
    {
        return $this->inProgressUploadCount($value);
    }

    public function pausedWebhookCount($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('paused_webhook_count', $operator, $value);
    }

        //  This is an alias function of pausedWebhookCount
    public function paused_webhook_count($value)
    {
        return $this->pausedWebhookCount($value);
    }

    public function currentMonthEgressBytes($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('current_month_egress_bytes', $operator, $value);
    }

        //  This is an alias function of currentMonthEgressBytes
    public function current_month_egress_bytes($value)
    {
        return $this->currentMonthEgressBytes($value);
    }

    public function currentMonthIngressBytes($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('current_month_ingress_bytes', $operator, $value);
    }

        //  This is an alias function of currentMonthIngressBytes
    public function current_month_ingress_bytes($value)
    {
        return $this->currentMonthIngressBytes($value);
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
