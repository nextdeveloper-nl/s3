<?php

namespace NextDeveloper\S3\Database\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use NextDeveloper\Commons\Database\Traits\HasStates;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use NextDeveloper\Commons\Database\Traits\Filterable;
use NextDeveloper\S3\Database\Observers\ServersObserver;
use NextDeveloper\Commons\Database\Traits\UuidId;
use NextDeveloper\Commons\Database\Traits\HasObject;
use NextDeveloper\Commons\Common\Cache\Traits\CleanCache;
use NextDeveloper\Commons\Database\Traits\Taggable;
use NextDeveloper\Commons\Database\Traits\RunAsAdministrator;

/**
 * Servers model.
 *
 * @package  NextDeveloper\S3\Database\Models
 * @property integer $id
 * @property string $uuid
 * @property integer $iam_account_id
 * @property integer $iam_user_id
 * @property string $hostname
 * @property string $name
 * @property string $agent_api_key
 * @property string $agent_version
 * @property string $seaweedfs_version
 * @property string $agent_status
 * @property \Carbon\Carbon $agent_last_seen_at
 * @property \Carbon\Carbon $agent_connected_at
 * @property string $health
 * @property string $health_summary
 * @property $components
 * @property float $price_per_gb
 * @property integer $common_currency_id
 * @property boolean $is_public
 * @property integer $marketplace_product_id
 * @property array $tags
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 */
class Servers extends Model
{
    use Filterable, UuidId, CleanCache, Taggable, HasStates, RunAsAdministrator, HasObject;
    use SoftDeletes;

    public $timestamps = true;

    protected $table = 's3_servers';


    /**
     @var array
     */
    protected $guarded = [];

    protected $fillable = [
            'iam_account_id',
            'iam_user_id',
            'hostname',
            'name',
            'agent_api_key',
            'agent_version',
            'seaweedfs_version',
            'agent_status',
            'agent_last_seen_at',
            'agent_connected_at',
            'health',
            'health_summary',
            'components',
            'price_per_gb',
            'common_currency_id',
            'is_public',
            'marketplace_product_id',
            'tags',
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
    'hostname' => 'string',
    'name' => 'string',
    'agent_api_key' => 'string',
    'agent_version' => 'string',
    'seaweedfs_version' => 'string',
    'agent_status' => 'string',
    'agent_last_seen_at' => 'datetime',
    'agent_connected_at' => 'datetime',
    'health' => 'string',
    'health_summary' => 'string',
    'components' => 'array',
    'price_per_gb' => 'float',
    'common_currency_id' => 'integer',
    'is_public' => 'boolean',
    'marketplace_product_id' => 'integer',
    'tags' => \NextDeveloper\Commons\Database\Casts\TextArray::class,
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'deleted_at' => 'datetime',
    ];

    /**
     We are casting data fields.
     *
     @var array
     */
    protected $dates = [
    'agent_last_seen_at',
    'agent_connected_at',
    'created_at',
    'updated_at',
    'deleted_at',
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
        parent::observe(ServersObserver::class);

        self::registerScopes();
    }

    public static function registerScopes()
    {
        $globalScopes = config('s3.scopes.global');
        $modelScopes = config('s3.scopes.s3_servers');

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

    public function buckets() : \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\NextDeveloper\S3\Database\Models\Buckets::class);
    }

    public function serverTelemetries() : \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\NextDeveloper\S3\Database\Models\ServerTelemetries::class);
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    use \NextDeveloper\Events\Database\Traits\HasAgentCommands;

    public function getAgentType(): string
    {
        return 's3';
    }
}
