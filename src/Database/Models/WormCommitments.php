<?php

namespace NextDeveloper\S3\Database\Models;

use NextDeveloper\Commons\Database\Traits\HasStates;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use NextDeveloper\Commons\Database\Traits\Filterable;
use NextDeveloper\S3\Database\Observers\WormCommitmentsObserver;
use NextDeveloper\Commons\Database\Traits\UuidId;
use NextDeveloper\Commons\Database\Traits\HasObject;
use NextDeveloper\Commons\Common\Cache\Traits\CleanCache;
use NextDeveloper\Commons\Database\Traits\Taggable;
use NextDeveloper\Commons\Database\Traits\RunAsAdministrator;

/**
 * WormCommitments model.
 *
 * @package  NextDeveloper\S3\Database\Models
 * @property integer $id
 * @property string $uuid
 * @property integer $s3_bucket_id
 * @property integer $s3_account_id
 * @property integer $iam_account_id
 * @property integer $common_currency_id
 * @property string $mode
 * @property integer $retention_days
 * @property integer $quota_bytes
 * @property $price_per_gb_mo
 * @property $deposit_amount
 * @property \Carbon\Carbon $committed_at
 * @property \Carbon\Carbon $locks_until
 * @property string $status
 * @property \Carbon\Carbon $cancelled_at
 * @property \Carbon\Carbon $expired_at
 * @property \Carbon\Carbon $purged_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class WormCommitments extends Model
{
    use Filterable, UuidId, CleanCache, Taggable, HasStates, RunAsAdministrator, HasObject;

    public $timestamps = true;

    protected $table = 's3_worm_commitments';


    /**
     @var array
     */
    protected $guarded = [];

    protected $fillable = [
            's3_bucket_id',
            's3_account_id',
            'iam_account_id',
            'common_currency_id',
            'mode',
            'retention_days',
            'quota_bytes',
            'price_per_gb_mo',
            'deposit_amount',
            'committed_at',
            'locks_until',
            'status',
            'cancelled_at',
            'expired_at',
            'purged_at',
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
    's3_bucket_id' => 'integer',
    's3_account_id' => 'integer',
    'common_currency_id' => 'integer',
    'mode' => 'string',
    'retention_days' => 'integer',
    'quota_bytes' => 'integer',
    'committed_at' => 'datetime',
    'locks_until' => 'datetime',
    'status' => 'string',
    'cancelled_at' => 'datetime',
    'expired_at' => 'datetime',
    'purged_at' => 'datetime',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
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
    'expired_at',
    'purged_at',
    'created_at',
    'updated_at',
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
        parent::observe(WormCommitmentsObserver::class);

        self::registerScopes();
    }

    public static function registerScopes()
    {
        $globalScopes = config('s3.scopes.global');
        $modelScopes = config('s3.scopes.s3_worm_commitments');

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

    public function buckets() : \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\NextDeveloper\S3\Database\Models\Buckets::class);
    }

    public function accounts() : \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\NextDeveloper\S3\Database\Models\Accounts::class);
    }

    public function depositLedgers() : \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\NextDeveloper\S3\Database\Models\DepositLedgers::class);
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
