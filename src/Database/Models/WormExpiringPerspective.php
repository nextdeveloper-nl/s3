<?php

namespace NextDeveloper\S3\Database\Models;

use NextDeveloper\Commons\Database\Traits\HasStates;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use NextDeveloper\Commons\Database\Traits\Filterable;
use NextDeveloper\S3\Database\Observers\WormExpiringPerspectiveObserver;
use NextDeveloper\Commons\Database\Traits\UuidId;
use NextDeveloper\Commons\Database\Traits\HasObject;
use NextDeveloper\Commons\Common\Cache\Traits\CleanCache;
use NextDeveloper\Commons\Database\Traits\Taggable;
use NextDeveloper\Commons\Database\Traits\RunAsAdministrator;

/**
 * WormExpiringPerspective model.
 *
 * @package  NextDeveloper\S3\Database\Models
 * @property integer $s3_worm_commitment_id
 * @property string $s3_worm_commitment_uuid
 * @property integer $s3_bucket_id
 * @property integer $s3_account_id
 * @property integer $iam_account_id
 * @property string $mode
 * @property integer $retention_days
 * @property integer $quota_bytes
 * @property $deposit_amount
 * @property $price_per_gb_mo
 * @property \Carbon\Carbon $committed_at
 * @property \Carbon\Carbon $locks_until
 * @property string $status
 * @property \Carbon\Carbon $cancelled_at
 * @property string $s3_account_slug
 * @property string $bucket_name
 * @property integer $days_until_expiry
 * @property boolean $is_expired
 * @property $deposit_refund_estimate
 */
class WormExpiringPerspective extends Model
{
    use Filterable, UuidId, CleanCache, Taggable, HasStates, RunAsAdministrator, HasObject;

    public $timestamps = false;

    protected $table = 's3_worm_expiring_perspective';


    /**
     @var array
     */
    protected $guarded = [];

    protected $fillable = [
            's3_worm_commitment_id',
            's3_worm_commitment_uuid',
            's3_bucket_id',
            's3_account_id',
            'iam_account_id',
            'mode',
            'retention_days',
            'quota_bytes',
            'deposit_amount',
            'price_per_gb_mo',
            'committed_at',
            'locks_until',
            'status',
            'cancelled_at',
            's3_account_slug',
            'bucket_name',
            'days_until_expiry',
            'is_expired',
            'deposit_refund_estimate',
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
    's3_worm_commitment_id' => 'integer',
    's3_bucket_id' => 'integer',
    's3_account_id' => 'integer',
    'mode' => 'string',
    'retention_days' => 'integer',
    'quota_bytes' => 'integer',
    'committed_at' => 'datetime',
    'locks_until' => 'datetime',
    'status' => 'string',
    'cancelled_at' => 'datetime',
    's3_account_slug' => 'string',
    'bucket_name' => 'string',
    'days_until_expiry' => 'integer',
    'is_expired' => 'boolean',
    ];

    /**
     We are casting data fields.
     *
     @var array
     */
    protected $dates = [
    'committed_at',
    'locks_until',
    'cancelled_at',
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
        parent::observe(WormExpiringPerspectiveObserver::class);

        self::registerScopes();
    }

    public static function registerScopes()
    {
        $globalScopes = config('s3.scopes.global');
        $modelScopes = config('s3.scopes.s3_worm_expiring_perspective');

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
