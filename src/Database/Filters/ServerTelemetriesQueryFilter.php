<?php

namespace NextDeveloper\S3\Database\Filters;

use Illuminate\Database\Eloquent\Builder;
use NextDeveloper\Commons\Database\Filters\AbstractQueryFilter;
    

/**
 * This class automatically puts where clause on database so that use can filter
 * data returned from the query.
 */
class ServerTelemetriesQueryFilter extends AbstractQueryFilter
{

    /**
     * @var Builder
     */
    protected $builder;

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
    
    public function reportedAtStart($date)
    {
        return $this->builder->where('reported_at', '>=', $date);
    }

    public function reportedAtEnd($date)
    {
        return $this->builder->where('reported_at', '<=', $date);
    }

    //  This is an alias function of reportedAt
    public function reported_at_start($value)
    {
        return $this->reportedAtStart($value);
    }

    //  This is an alias function of reportedAt
    public function reported_at_end($value)
    {
        return $this->reportedAtEnd($value);
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
