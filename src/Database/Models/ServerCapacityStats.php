<?php

namespace NextDeveloper\S3\Database\Models;

use NextDeveloper\Commons\Database\Traits\HasStates;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use NextDeveloper\Commons\Database\Traits\Filterable;
use NextDeveloper\S3\Database\Observers\ServerCapacityStatsObserver;
use NextDeveloper\Commons\Database\Traits\UuidId;
use NextDeveloper\Commons\Database\Traits\HasObject;
use NextDeveloper\Commons\Common\Cache\Traits\CleanCache;
use NextDeveloper\Commons\Database\Traits\Taggable;
use NextDeveloper\Commons\Database\Traits\RunAsAdministrator;

/**
 * ServerCapacityStats model.
 *
 * @package  NextDeveloper\S3\Database\Models
 * @property integer $s3_server_id
 * @property string $s3_server_uuid
 * @property string $hostname
 * @property string $name
 * @property string $health
 * @property string $agent_status
 * @property \Carbon\Carbon $agent_last_seen_at
 * @property \Carbon\Carbon $latest_reported_at
 * @property boolean $master_reachable
 * @property integer $volume_count
 * @property integer $volumes_degraded
 * @property integer $capacity_bytes_total
 * @property integer $capacity_bytes_used
 * @property $capacity_pct
 * @property $capacity_gb_total
 * @property $capacity_gb_used
 * @property integer $hosted_bucket_count
 * @property integer $hosted_account_count
 * @property $minutes_since_last_report
 */
class ServerCapacityStats extends Model
{
    use Filterable, UuidId, CleanCache, Taggable, HasStates, RunAsAdministrator, HasObject;

    public $timestamps = false;

    protected $table = 's3_server_capacity_stats';


    /**
     @var array
     */
    protected $guarded = [];

    protected $fillable = [
            's3_server_id',
            's3_server_uuid',
            'hostname',
            'name',
            'health',
            'agent_status',
            'agent_last_seen_at',
            'latest_reported_at',
            'master_reachable',
            'volume_count',
            'volumes_degraded',
            'capacity_bytes_total',
            'capacity_bytes_used',
            'capacity_pct',
            'capacity_gb_total',
            'capacity_gb_used',
            'hosted_bucket_count',
            'hosted_account_count',
            'minutes_since_last_report',
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
    's3_server_id' => 'integer',
    'hostname' => 'string',
    'name' => 'string',
    'health' => 'string',
    'agent_status' => 'string',
    'agent_last_seen_at' => 'datetime',
    'latest_reported_at' => 'datetime',
    'master_reachable' => 'boolean',
    'volume_count' => 'integer',
    'volumes_degraded' => 'integer',
    'capacity_bytes_total' => 'integer',
    'capacity_bytes_used' => 'integer',
    'hosted_bucket_count' => 'integer',
    'hosted_account_count' => 'integer',
    ];

    /**
     We are casting data fields.
     *
     @var array
     */
    protected $dates = [
    'agent_last_seen_at',
    'latest_reported_at',
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
        parent::observe(ServerCapacityStatsObserver::class);

        self::registerScopes();
    }

    public static function registerScopes()
    {
        $globalScopes = config('s3.scopes.global');
        $modelScopes = config('s3.scopes.s3_server_capacity_stats');

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

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
