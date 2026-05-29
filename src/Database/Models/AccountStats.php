<?php

namespace NextDeveloper\S3\Database\Models;

use NextDeveloper\Commons\Database\Traits\HasStates;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use NextDeveloper\Commons\Database\Traits\Filterable;
use NextDeveloper\S3\Database\Observers\AccountStatsObserver;
use NextDeveloper\Commons\Database\Traits\UuidId;
use NextDeveloper\Commons\Database\Traits\HasObject;
use NextDeveloper\Commons\Common\Cache\Traits\CleanCache;
use NextDeveloper\Commons\Database\Traits\Taggable;
use NextDeveloper\Commons\Database\Traits\RunAsAdministrator;

/**
 * AccountStats model.
 *
 * @package  NextDeveloper\S3\Database\Models
 * @property integer $s3_account_id
 * @property string $s3_account_uuid
 * @property integer $iam_account_id
 * @property string $slug
 * @property string $status
 * @property \Carbon\Carbon $blocked_at
 * @property string $blocked_reason
 * @property \Carbon\Carbon $usage_checked_at
 * @property integer $quota_storage_bytes
 * @property integer $storage_bytes_used
 * @property $storage_pct
 * @property integer $quota_egress_bytes_mo
 * @property integer $egress_bytes_mo_used
 * @property $egress_pct
 * @property integer $quota_max_objects
 * @property integer $object_count
 * @property $object_pct
 * @property integer $quota_max_buckets
 * @property integer $bucket_count
 * @property $bucket_pct
 * @property integer $active_key_count
 * @property integer $in_progress_upload_count
 * @property integer $paused_webhook_count
 * @property integer $current_month_egress_bytes
 * @property integer $current_month_ingress_bytes
 */
class AccountStats extends Model
{
    use Filterable, UuidId, CleanCache, Taggable, HasStates, RunAsAdministrator, HasObject;

    public $timestamps = false;

    protected $table = 's3_account_stats';


    /**
     @var array
     */
    protected $guarded = [];

    protected $fillable = [
            's3_account_id',
            's3_account_uuid',
            'iam_account_id',
            'slug',
            'status',
            'blocked_at',
            'blocked_reason',
            'usage_checked_at',
            'quota_storage_bytes',
            'storage_bytes_used',
            'storage_pct',
            'quota_egress_bytes_mo',
            'egress_bytes_mo_used',
            'egress_pct',
            'quota_max_objects',
            'object_count',
            'object_pct',
            'quota_max_buckets',
            'bucket_count',
            'bucket_pct',
            'active_key_count',
            'in_progress_upload_count',
            'paused_webhook_count',
            'current_month_egress_bytes',
            'current_month_ingress_bytes',
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
    's3_account_id' => 'integer',
    'slug' => 'string',
    'status' => 'string',
    'blocked_at' => 'datetime',
    'blocked_reason' => 'string',
    'usage_checked_at' => 'datetime',
    'quota_storage_bytes' => 'integer',
    'storage_bytes_used' => 'integer',
    'quota_egress_bytes_mo' => 'integer',
    'egress_bytes_mo_used' => 'integer',
    'quota_max_objects' => 'integer',
    'object_count' => 'integer',
    'quota_max_buckets' => 'integer',
    'bucket_count' => 'integer',
    'active_key_count' => 'integer',
    'in_progress_upload_count' => 'integer',
    'paused_webhook_count' => 'integer',
    'current_month_egress_bytes' => 'integer',
    'current_month_ingress_bytes' => 'integer',
    ];

    /**
     We are casting data fields.
     *
     @var array
     */
    protected $dates = [
    'blocked_at',
    'usage_checked_at',
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
        parent::observe(AccountStatsObserver::class);

        self::registerScopes();
    }

    public static function registerScopes()
    {
        $globalScopes = config('s3.scopes.global');
        $modelScopes = config('s3.scopes.s3_account_stats');

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
