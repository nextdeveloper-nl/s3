<?php

namespace NextDeveloper\S3\Database\Filters;

use Illuminate\Database\Eloquent\Builder;
use NextDeveloper\Commons\Database\Filters\AbstractQueryFilter;
        

/**
 * This class automatically puts where clause on database so that use can filter
 * data returned from the query.
 */
class ServersPerspectiveQueryFilter extends AbstractQueryFilter
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
    
    public function hostname($value)
    {
        return $this->builder->where('hostname', 'ilike', '%' . $value . '%');
    }

        
    public function name($value)
    {
        return $this->builder->where('name', 'ilike', '%' . $value . '%');
    }

        
    public function agentVersion($value)
    {
        return $this->builder->where('agent_version', 'ilike', '%' . $value . '%');
    }

        //  This is an alias function of agentVersion
    public function agent_version($value)
    {
        return $this->agentVersion($value);
    }
        
    public function seaweedfsVersion($value)
    {
        return $this->builder->where('seaweedfs_version', 'ilike', '%' . $value . '%');
    }

        //  This is an alias function of seaweedfsVersion
    public function seaweedfs_version($value)
    {
        return $this->seaweedfsVersion($value);
    }
        
    public function agentStatus($value)
    {
        return $this->builder->where('agent_status', 'ilike', '%' . $value . '%');
    }

        //  This is an alias function of agentStatus
    public function agent_status($value)
    {
        return $this->agentStatus($value);
    }
        
    public function health($value)
    {
        return $this->builder->where('health', 'ilike', '%' . $value . '%');
    }

        
    public function healthSummary($value)
    {
        return $this->builder->where('health_summary', 'ilike', '%' . $value . '%');
    }

        //  This is an alias function of healthSummary
    public function health_summary($value)
    {
        return $this->healthSummary($value);
    }
    
    public function masterPeers($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('master_peers', $operator, $value);
    }

        //  This is an alias function of masterPeers
    public function master_peers($value)
    {
        return $this->masterPeers($value);
    }
    
    public function volumeTotal($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('volume_total', $operator, $value);
    }

        //  This is an alias function of volumeTotal
    public function volume_total($value)
    {
        return $this->volumeTotal($value);
    }
    
    public function volumesWritable($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('volumes_writable', $operator, $value);
    }

        //  This is an alias function of volumesWritable
    public function volumes_writable($value)
    {
        return $this->volumesWritable($value);
    }
    
    public function volumesDegraded($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('volumes_degraded', $operator, $value);
    }

        //  This is an alias function of volumesDegraded
    public function volumes_degraded($value)
    {
        return $this->volumesDegraded($value);
    }
    
    public function volumesReadonly($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('volumes_readonly', $operator, $value);
    }

        //  This is an alias function of volumesReadonly
    public function volumes_readonly($value)
    {
        return $this->volumesReadonly($value);
    }
    
    public function capacityBytesTotal($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('capacity_bytes_total', $operator, $value);
    }

        //  This is an alias function of capacityBytesTotal
    public function capacity_bytes_total($value)
    {
        return $this->capacityBytesTotal($value);
    }
    
    public function capacityBytesUsed($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('capacity_bytes_used', $operator, $value);
    }

        //  This is an alias function of capacityBytesUsed
    public function capacity_bytes_used($value)
    {
        return $this->capacityBytesUsed($value);
    }
    
    public function s3BucketCount($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('s3_bucket_count', $operator, $value);
    }

        //  This is an alias function of s3BucketCount
    public function s3_bucket_count($value)
    {
        return $this->s3BucketCount($value);
    }
    
    public function hostedBucketCount($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('hosted_bucket_count', $operator, $value);
    }

        //  This is an alias function of hostedBucketCount
    public function hosted_bucket_count($value)
    {
        return $this->hostedBucketCount($value);
    }
    
    public function hostedAccountCount($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('hosted_account_count', $operator, $value);
    }

        //  This is an alias function of hostedAccountCount
    public function hosted_account_count($value)
    {
        return $this->hostedAccountCount($value);
    }
    
    public function agentLastSeenAtStart($date)
    {
        return $this->builder->where('agent_last_seen_at', '>=', $date);
    }

    public function agentLastSeenAtEnd($date)
    {
        return $this->builder->where('agent_last_seen_at', '<=', $date);
    }

    //  This is an alias function of agentLastSeenAt
    public function agent_last_seen_at_start($value)
    {
        return $this->agentLastSeenAtStart($value);
    }

    //  This is an alias function of agentLastSeenAt
    public function agent_last_seen_at_end($value)
    {
        return $this->agentLastSeenAtEnd($value);
    }

    public function agentConnectedAtStart($date)
    {
        return $this->builder->where('agent_connected_at', '>=', $date);
    }

    public function agentConnectedAtEnd($date)
    {
        return $this->builder->where('agent_connected_at', '<=', $date);
    }

    //  This is an alias function of agentConnectedAt
    public function agent_connected_at_start($value)
    {
        return $this->agentConnectedAtStart($value);
    }

    //  This is an alias function of agentConnectedAt
    public function agent_connected_at_end($value)
    {
        return $this->agentConnectedAtEnd($value);
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
