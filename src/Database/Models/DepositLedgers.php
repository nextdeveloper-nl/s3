<?php

namespace NextDeveloper\S3\Database\Models;

use NextDeveloper\Commons\Database\Traits\HasStates;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use NextDeveloper\Commons\Database\Traits\Filterable;
use NextDeveloper\S3\Database\Observers\DepositLedgersObserver;
use NextDeveloper\Commons\Database\Traits\UuidId;
use NextDeveloper\Commons\Database\Traits\HasObject;
use NextDeveloper\Commons\Common\Cache\Traits\CleanCache;
use NextDeveloper\Commons\Database\Traits\Taggable;
use NextDeveloper\Commons\Database\Traits\RunAsAdministrator;

/**
 * DepositLedgers model.
 *
 * @package  NextDeveloper\S3\Database\Models
 * @property integer $id
 * @property integer $s3_account_id
 * @property integer $s3_worm_commitment_id
 * @property integer $iam_account_id
 * @property string $type
 * @property $amount
 * @property integer $days_remaining
 * @property integer $days_total
 * @property string $reference
 * @property string $performed_by
 * @property string $notes
 * @property \Carbon\Carbon $performed_at
 */
class DepositLedgers extends Model
{
    use Filterable, UuidId, CleanCache, Taggable, HasStates, RunAsAdministrator, HasObject;

    public $timestamps = false;

    protected $table = 's3_deposit_ledger';


    /**
     @var array
     */
    protected $guarded = [];

    protected $fillable = [
            's3_account_id',
            's3_worm_commitment_id',
            'iam_account_id',
            'type',
            'amount',
            'days_remaining',
            'days_total',
            'reference',
            'performed_by',
            'notes',
            'performed_at',
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
    's3_worm_commitment_id' => 'integer',
    'type' => 'string',
    'days_remaining' => 'integer',
    'days_total' => 'integer',
    'reference' => 'string',
    'performed_by' => 'string',
    'notes' => 'string',
    'performed_at' => 'datetime',
    ];

    /**
     We are casting data fields.
     *
     @var array
     */
    protected $dates = [
    'performed_at',
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
        parent::observe(DepositLedgersObserver::class);

        self::registerScopes();
    }

    public static function registerScopes()
    {
        $globalScopes = config('s3.scopes.global');
        $modelScopes = config('s3.scopes.s3_deposit_ledger');

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

    public function wormCommitments() : \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\NextDeveloper\S3\Database\Models\WormCommitments::class);
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
