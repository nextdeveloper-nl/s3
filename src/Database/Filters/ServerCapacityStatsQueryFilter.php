<?php

namespace NextDeveloper\S3\Database\Filters;

use Illuminate\Database\Eloquent\Builder;
use NextDeveloper\Commons\Database\Filters\AbstractQueryFilter;
    

/**
 * This class automatically puts where clause on database so that use can filter
 * data returned from the query.
 */
class ServerCapacityStatsQueryFilter extends AbstractQueryFilter
{

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


    public function health($value)
    {
        return $this->builder->where('health', 'ilike', '%' . $value . '%');
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

    public function volumeCount($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('volume_count', $operator, $value);
    }

        //  This is an alias function of volumeCount
    public function volume_count($value)
    {
        return $this->volumeCount($value);
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

    public function latestReportedAtStart($date)
    {
        return $this->builder->where('latest_reported_at', '>=', $date);
    }

    public function latestReportedAtEnd($date)
    {
        return $this->builder->where('latest_reported_at', '<=', $date);
    }

    //  This is an alias function of latestReportedAt
    public function latest_reported_at_start($value)
    {
        return $this->latestReportedAtStart($value);
    }

    //  This is an alias function of latestReportedAt
    public function latest_reported_at_end($value)
    {
        return $this->latestReportedAtEnd($value);
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

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
