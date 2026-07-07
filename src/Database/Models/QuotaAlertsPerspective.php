<?php

namespace NextDeveloper\S3\Database\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use NextDeveloper\Commons\Database\Traits\HasStates;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use NextDeveloper\Commons\Database\Traits\Filterable;
use NextDeveloper\S3\Database\Observers\QuotaAlertsPerspectiveObserver;
use NextDeveloper\Commons\Database\Traits\UuidId;
use NextDeveloper\Commons\Database\Traits\HasObject;
use NextDeveloper\Commons\Common\Cache\Traits\CleanCache;
use NextDeveloper\Commons\Database\Traits\Taggable;
use NextDeveloper\Commons\Database\Traits\RunAsAdministrator;

/**
 * QuotaAlertsPerspective model.
 *
 * @package  NextDeveloper\S3\Database\Models
 * @property integer $id
 * @property string $uuid
 * @property integer $s3_account_id
 * @property integer $iam_account_id
 * @property integer $iam_user_id
 * @property string $s3_account_slug
 * @property integer $quota_storage_bytes
 * @property integer $storage_bytes_used
 * @property $storage_usage_pct
 * @property integer $quota_egress_bytes_mo
 * @property integer $egress_bytes_mo_used
 * @property $egress_usage_pct
 * @property string $severity
 * @property boolean $is_blocked
 * @property \Carbon\Carbon $blocked_at
 * @property string $blocked_reason
 * @property \Carbon\Carbon $usage_checked_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 */
class QuotaAlertsPerspective extends Model
{
    use Filterable, UuidId, CleanCache, Taggable, HasStates, RunAsAdministrator, HasObject;
    use SoftDeletes;

    public $timestamps = true;

    protected $table = 's3_quota_alerts_perspective';


    /**
     @var array
     */
    protected $guarded = [];

    protected $fillable = [
            's3_account_id',
            'iam_account_id',
            'iam_user_id',
            's3_account_slug',
            'quota_storage_bytes',
            'storage_bytes_used',
            'storage_usage_pct',
            'quota_egress_bytes_mo',
            'egress_bytes_mo_used',
            'egress_usage_pct',
            'severity',
            'is_blocked',
            'blocked_at',
            'blocked_reason',
            'usage_checked_at',
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
    's3_account_slug' => 'string',
    'quota_storage_bytes' => 'integer',
    'storage_bytes_used' => 'integer',
    'quota_egress_bytes_mo' => 'integer',
    'egress_bytes_mo_used' => 'integer',
    'severity' => 'string',
    'is_blocked' => 'boolean',
    'blocked_at' => 'datetime',
    'blocked_reason' => 'string',
    'usage_checked_at' => 'datetime',
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
    'blocked_at',
    'usage_checked_at',
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
        parent::observe(QuotaAlertsPerspectiveObserver::class);

        self::registerScopes();
    }

    public static function registerScopes()
    {
        $globalScopes = config('s3.scopes.global');
        $modelScopes = config('s3.scopes.s3_quota_alerts_perspective');

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
