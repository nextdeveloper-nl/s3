<?php

namespace NextDeveloper\S3\Database\Filters;

use Illuminate\Database\Eloquent\Builder;
use NextDeveloper\Commons\Database\Filters\AbstractQueryFilter;
        

/**
 * This class automatically puts where clause on database so that use can filter
 * data returned from the query.
 */
class WebhookDeliveriesQueryFilter extends AbstractQueryFilter
{

    /**
     * @var Builder
     */
    protected $builder;

    public function eventType($value)
    {
        return $this->builder->where('event_type', 'ilike', '%' . $value . '%');
    }

        //  This is an alias function of eventType
    public function event_type($value)
    {
        return $this->eventType($value);
    }

    public function objectKey($value)
    {
        return $this->builder->where('object_key', 'ilike', '%' . $value . '%');
    }

        //  This is an alias function of objectKey
    public function object_key($value)
    {
        return $this->objectKey($value);
    }

    public function statusCode($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('status_code', $operator, $value);
    }

        //  This is an alias function of statusCode
    public function status_code($value)
    {
        return $this->statusCode($value);
    }

    public function attempt($value)
    {
        $operator = substr($value, 0, 1);

        if ($operator != '<' || $operator != '>') {
            $operator = '=';
        } else {
            $value = substr($value, 1);
        }

        return $this->builder->where('attempt', $operator, $value);
    }


    public function nextRetryAtStart($date)
    {
        return $this->builder->where('next_retry_at', '>=', $date);
    }

    public function nextRetryAtEnd($date)
    {
        return $this->builder->where('next_retry_at', '<=', $date);
    }

    //  This is an alias function of nextRetryAt
    public function next_retry_at_start($value)
    {
        return $this->nextRetryAtStart($value);
    }

    //  This is an alias function of nextRetryAt
    public function next_retry_at_end($value)
    {
        return $this->nextRetryAtEnd($value);
    }

    public function deliveredAtStart($date)
    {
        return $this->builder->where('delivered_at', '>=', $date);
    }

    public function deliveredAtEnd($date)
    {
        return $this->builder->where('delivered_at', '<=', $date);
    }

    //  This is an alias function of deliveredAt
    public function delivered_at_start($value)
    {
        return $this->deliveredAtStart($value);
    }

    //  This is an alias function of deliveredAt
    public function delivered_at_end($value)
    {
        return $this->deliveredAtEnd($value);
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

    public function s3WebhookId($value)
    {
            $s3Webhook = \NextDeveloper\S3\Database\Models\Webhooks::where('uuid', $value)->first();

        if($s3Webhook) {
            return $this->builder->where('s3_webhook_id', '=', $s3Webhook->id);
        }
    }

        //  This is an alias function of s3Webhook
    public function s3_webhook_id($value)
    {
        return $this->s3Webhook($value);
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

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
