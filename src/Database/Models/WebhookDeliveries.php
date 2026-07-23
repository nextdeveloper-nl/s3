<?php

namespace NextDeveloper\S3\Database\Models;

use NextDeveloper\Commons\Database\Traits\HasStates;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use NextDeveloper\Commons\Database\Traits\Filterable;
use NextDeveloper\S3\Database\Observers\WebhookDeliveriesObserver;
use NextDeveloper\Commons\Database\Traits\UuidId;
use NextDeveloper\Commons\Database\Traits\HasObject;
use NextDeveloper\Commons\Common\Cache\Traits\CleanCache;
use NextDeveloper\Commons\Database\Traits\Taggable;
use NextDeveloper\Commons\Database\Traits\RunAsAdministrator;

/**
 * WebhookDeliveries model.
 *
 * @package  NextDeveloper\S3\Database\Models
 * @property integer $id
 * @property integer $s3_webhook_id
 * @property integer $s3_account_id
 * @property string $event_type
 * @property string $object_key
 * @property $payload
 * @property integer $status_code
 * @property integer $attempt
 * @property \Carbon\Carbon $next_retry_at
 * @property \Carbon\Carbon $delivered_at
 * @property \Carbon\Carbon $created_at
 */
class WebhookDeliveries extends Model
{
    use Filterable, UuidId, CleanCache, Taggable, HasStates, RunAsAdministrator, HasObject;

    public $timestamps = false;

    protected $table = 's3_webhook_deliveries';


    /**
     @var array
     */
    protected $guarded = [];

    protected $fillable = [
            's3_webhook_id',
            's3_account_id',
            'event_type',
            'object_key',
            'payload',
            'status_code',
            'attempt',
            'next_retry_at',
            'delivered_at',
    ];

    /**
      Here we have the fulltext fields. We can use these for fulltext search if enabled.
     */
    protected $fullTextFields = [

    ];

    /**
     @var array
     */
    protected $appends = [

    ];

    /**
     We are casting fields to objects so that we can work on them better
     *
     @var array
     */
    protected $casts = [
    'id' => 'integer',
    's3_webhook_id' => 'integer',
    's3_account_id' => 'integer',
    'event_type' => 'string',
    'object_key' => 'string',
    'payload' => 'array',
    'status_code' => 'integer',
    'attempt' => 'integer',
    'next_retry_at' => 'datetime',
    'delivered_at' => 'datetime',
    'created_at' => 'datetime',
    ];

    /**
     We are casting data fields.
     *
     @var array
     */
    protected $dates = [
    'next_retry_at',
    'delivered_at',
    'created_at',
    ];

    /**
     @var array
     */
    protected $with = [

    ];

    /**
     @var int
     */
    protected $perPage = 20;

    /**
     @return void
     */
    public static function boot()
    {
        parent::boot();

        //  We create and add Observer even if we wont use it.
        parent::observe(WebhookDeliveriesObserver::class);

        self::registerScopes();
    }

    public static function registerScopes()
    {
        $globalScopes = config('s3.scopes.global');
        $modelScopes = config('s3.scopes.s3_webhook_deliveries');

        if(!$modelScopes) { $modelScopes = [];
        }
        if (!$globalScopes) { $globalScopes = [];
        }

        $scopes = array_merge(
            $globalScopes,
            $modelScopes
        );

        if($scopes) {
            foreach ($scopes as $scope) {
                static::addGlobalScope(app($scope));
            }
        }
    }

    public function webhooks() : \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\NextDeveloper\S3\Database\Models\Webhooks::class);
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
