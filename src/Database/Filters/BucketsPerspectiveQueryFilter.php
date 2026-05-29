<?php

namespace NextDeveloper\S3\Database\Filters;

use Illuminate\Database\Eloquent\Builder;
use NextDeveloper\Commons\Database\Filters\AbstractQueryFilter;
                

/**
 * This class automatically puts where clause on database so that use can filter
 * data returned from the query.
 */
class BucketsPerspectiveQueryFilter extends AbstractQueryFilter
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
    
    public function name($value)
    {
        return $this->builder->where('name', 'ilike', '%' . $value . '%');
    }

        
    public function versioning($value)
    {
        return $this->builder->where('versioning', 'ilike', '%' . $value . '%');
    }

        
    public function objectLockMode($value)
    {
        return $this->builder->where('object_lock_mode', 'ilike', '%' . $value . '%');
    }

        //  This is an alias function of objectLockMode
    public function object_lock_mode($value)
    {
        return $this->objectLockMode($value);
    }
        
    public function replicaHealth($value)
    {
        return $this->builder->where('replica_health', 'ilike', '%' . $value . '%');
    }

        //  This is an alias function of replicaHealth
    public function replica_health($value)
    {
        return $this->replicaHealth($value);
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
        
    public function s3ServerHostname($value)
    {
        return $this->builder->where('s3_server_hostname', 'ilike', '%' . $value . '%');
    }

        //  This is an alias function of s3ServerHostname
    public function s3_server_hostname($value)
    {
        return $this->s3ServerHostname($value);
    }
        
    public function s3ServerHealth($value)
    {
        return $this->builder->where('s3_server_health', 'ilike', '%' . $value . '%');
    }

        //  This is an alias function of s3ServerHealth
    public function s3_server_health($value)
    {
        return $this->s3ServerHealth($value);
    }
        
    public function wormStatus($value)
    {
        return $this->builder->where('worm_status', 'ilike', '%' . $value . '%');
    }

        //  This is an alias function of wormStatus
    public function worm_status($value)
    {
        return $this->wormStatus($value);
    }
    
    public function replicationFactor($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('replication_factor', $operator, $value);
    }

        //  This is an alias function of replicationFactor
    public function replication_factor($value)
    {
        return $this->replicationFactor($value);
    }
    
    public function objectLockDays($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('object_lock_days', $operator, $value);
    }

        //  This is an alias function of objectLockDays
    public function object_lock_days($value)
    {
        return $this->objectLockDays($value);
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
    
    public function sizeBytes($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('size_bytes', $operator, $value);
    }

        //  This is an alias function of sizeBytes
    public function size_bytes($value)
    {
        return $this->sizeBytes($value);
    }
    
    public function activeWebhookCount($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('active_webhook_count', $operator, $value);
    }

        //  This is an alias function of activeWebhookCount
    public function active_webhook_count($value)
    {
        return $this->activeWebhookCount($value);
    }
    
    public function inProgressUploads($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('in_progress_uploads', $operator, $value);
    }

        //  This is an alias function of inProgressUploads
    public function in_progress_uploads($value)
    {
        return $this->inProgressUploads($value);
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

    public function wormLocksUntilStart($date)
    {
        return $this->builder->where('worm_locks_until', '>=', $date);
    }

    public function wormLocksUntilEnd($date)
    {
        return $this->builder->where('worm_locks_until', '<=', $date);
    }

    //  This is an alias function of wormLocksUntil
    public function worm_locks_until_start($value)
    {
        return $this->wormLocksUntilStart($value);
    }

    //  This is an alias function of wormLocksUntil
    public function worm_locks_until_end($value)
    {
        return $this->wormLocksUntilEnd($value);
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
