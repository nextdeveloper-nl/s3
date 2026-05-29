<?php

namespace NextDeveloper\S3\Database\Filters;

use Illuminate\Database\Eloquent\Builder;
use NextDeveloper\Commons\Database\Filters\AbstractQueryFilter;
        

/**
 * This class automatically puts where clause on database so that use can filter
 * data returned from the query.
 */
class ServersQueryFilter extends AbstractQueryFilter
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

        
    public function agentApiKey($value)
    {
        return $this->builder->where('agent_api_key', 'ilike', '%' . $value . '%');
    }

        //  This is an alias function of agentApiKey
    public function agent_api_key($value)
    {
        return $this->agentApiKey($value);
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
