<?php

namespace NextDeveloper\S3\Database\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use NextDeveloper\Commons\Database\Traits\HasStates;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use NextDeveloper\Commons\Database\Traits\Filterable;
use NextDeveloper\S3\Database\Observers\AccountsPerspectiveObserver;
use NextDeveloper\Commons\Database\Traits\UuidId;
use NextDeveloper\Commons\Database\Traits\HasObject;
use NextDeveloper\Commons\Common\Cache\Traits\CleanCache;
use NextDeveloper\Commons\Database\Traits\Taggable;
use NextDeveloper\Commons\Database\Traits\RunAsAdministrator;

/**
 * AccountsPerspective model.
 *
 * @package  NextDeveloper\S3\Database\Models
 * @property integer $id
 * @property string $uuid
 * @property integer $iam_account_id
 * @property integer $iam_user_id
 * @property string $slug
 * @property string $status
 * @property integer $quota_storage_bytes
 * @property integer $quota_egress_bytes_mo
 * @property integer $quota_max_buckets
 * @property integer $quota_max_objects
 * @property integer $storage_bytes_used
 * @property integer $egress_bytes_mo_used
 * @property integer $object_count
 * @property \Carbon\Carbon $usage_checked_at
 * @property \Carbon\Carbon $blocked_at
 * @property string $blocked_reason
 * @property array $tags
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * @property $storage_pct
 * @property $egress_pct
 * @property $object_pct
 * @property integer $bucket_count
 * @property $bucket_pct
 * @property integer $active_key_count
 * @property integer $current_month_egress_bytes
 * @property integer $current_month_ingress_bytes
 * @property integer $included_egress_bytes_mo
 * @property integer $egress_overage_bytes
 */
class AccountsPerspective extends Model
{
    use Filterable, UuidId, CleanCache, Taggable, HasStates, RunAsAdministrator, HasObject;
    use SoftDeletes;

    public $timestamps = true;

    protected $table = 's3_accounts_perspective';


    /**
     @var array
     */
    protected $guarded = [];

    protected $fillable = [
            'iam_account_id',
            'iam_user_id',
            'slug',
            'status',
            'quota_storage_bytes',
            'quota_egress_bytes_mo',
            'quota_max_buckets',
            'quota_max_objects',
            'storage_bytes_used',
            'egress_bytes_mo_used',
            'object_count',
            'usage_checked_at',
            'blocked_at',
            'blocked_reason',
            'tags',
            'storage_pct',
            'egress_pct',
            'object_pct',
            'bucket_count',
            'bucket_pct',
            'active_key_count',
            'current_month_egress_bytes',
            'current_month_ingress_bytes',
            'included_egress_bytes_mo',
            'egress_overage_bytes',
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
    'slug' => 'string',
    'status' => 'string',
    'quota_storage_bytes' => 'integer',
    'quota_egress_bytes_mo' => 'integer',
    'quota_max_buckets' => 'integer',
    'quota_max_objects' => 'integer',
    'storage_bytes_used' => 'integer',
    'egress_bytes_mo_used' => 'integer',
    'object_count' => 'integer',
    'usage_checked_at' => 'datetime',
    'blocked_at' => 'datetime',
    'blocked_reason' => 'string',
    'tags' => \NextDeveloper\Commons\Database\Casts\TextArray::class,
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'deleted_at' => 'datetime',
    'bucket_count' => 'integer',
    'active_key_count' => 'integer',
    'current_month_egress_bytes' => 'integer',
    'current_month_ingress_bytes' => 'integer',
    'included_egress_bytes_mo' => 'integer',
    'egress_overage_bytes' => 'integer',
    ];

    /**
     We are casting data fields.
     *
     @var array
     */
    protected $dates = [
    'usage_checked_at',
    'blocked_at',
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
        parent::observe(AccountsPerspectiveObserver::class);

        self::registerScopes();
    }

    public static function registerScopes()
    {
        $globalScopes = config('s3.scopes.global');
        $modelScopes = config('s3.scopes.s3_accounts_perspective');

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
