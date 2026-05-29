<?php

namespace NextDeveloper\S3\Database\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use NextDeveloper\Commons\Database\Traits\HasStates;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use NextDeveloper\Commons\Database\Traits\Filterable;
use NextDeveloper\S3\Database\Observers\BucketsObserver;
use NextDeveloper\Commons\Database\Traits\UuidId;
use NextDeveloper\Commons\Database\Traits\HasObject;
use NextDeveloper\Commons\Common\Cache\Traits\CleanCache;
use NextDeveloper\Commons\Database\Traits\Taggable;
use NextDeveloper\Commons\Database\Traits\RunAsAdministrator;

/**
 * Buckets model.
 *
 * @package  NextDeveloper\S3\Database\Models
 * @property integer $id
 * @property string $uuid
 * @property integer $s3_account_id
 * @property integer $s3_server_id
 * @property integer $iam_account_id
 * @property integer $iam_user_id
 * @property string $name
 * @property integer $replication_factor
 * @property $lifecycle_rules
 * @property string $versioning
 * @property boolean $mfa_delete
 * @property boolean $object_lock_enabled
 * @property string $object_lock_mode
 * @property integer $object_lock_days
 * @property integer $object_count
 * @property integer $size_bytes
 * @property string $replica_health
 * @property string $status
 * @property array $tags
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 */
class Buckets extends Model
{
    use Filterable, UuidId, CleanCache, Taggable, HasStates, RunAsAdministrator, HasObject;
    use SoftDeletes;

    public $timestamps = true;

    protected $table = 's3_buckets';


    /**
     @var array
     */
    protected $guarded = [];

    protected $fillable = [
            's3_account_id',
            's3_server_id',
            'iam_account_id',
            'iam_user_id',
            'name',
            'replication_factor',
            'lifecycle_rules',
            'versioning',
            'mfa_delete',
            'object_lock_enabled',
            'object_lock_mode',
            'object_lock_days',
            'object_count',
            'size_bytes',
            'replica_health',
            'status',
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
    's3_account_id' => 'integer',
    's3_server_id' => 'integer',
    'name' => 'string',
    'replication_factor' => 'integer',
    'lifecycle_rules' => 'array',
    'versioning' => 'string',
    'mfa_delete' => 'boolean',
    'object_lock_enabled' => 'boolean',
    'object_lock_mode' => 'string',
    'object_lock_days' => 'integer',
    'object_count' => 'integer',
    'size_bytes' => 'integer',
    'replica_health' => 'string',
    'status' => 'string',
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
        parent::observe(BucketsObserver::class);

        self::registerScopes();
    }

    public static function registerScopes()
    {
        $globalScopes = config('s3.scopes.global');
        $modelScopes = config('s3.scopes.s3_buckets');

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

    public function accounts() : \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\NextDeveloper\S3\Database\Models\Accounts::class);
    }
    
    public function servers() : \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\NextDeveloper\S3\Database\Models\Servers::class);
    }
    
    public function webhooks() : \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\NextDeveloper\S3\Database\Models\Webhooks::class);
    }

    public function multipartUploads() : \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\NextDeveloper\S3\Database\Models\MultipartUploads::class);
    }

    public function wormCommitments() : \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\NextDeveloper\S3\Database\Models\WormCommitments::class);
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
