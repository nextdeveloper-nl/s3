<?php

namespace NextDeveloper\S3\Database\Models;

use NextDeveloper\Commons\Database\Traits\HasStates;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use NextDeveloper\Commons\Database\Traits\Filterable;
use NextDeveloper\S3\Database\Observers\AuditLogsObserver;
use NextDeveloper\Commons\Database\Traits\UuidId;
use NextDeveloper\Commons\Database\Traits\HasObject;
use NextDeveloper\Commons\Common\Cache\Traits\CleanCache;
use NextDeveloper\Commons\Database\Traits\Taggable;
use NextDeveloper\Commons\Database\Traits\RunAsAdministrator;

/**
 * AuditLogs model.
 *
 * @package  NextDeveloper\S3\Database\Models
 * @property integer $id
 * @property integer $iam_account_id
 * @property integer $iam_user_id
 * @property integer $s3_account_id
 * @property integer $s3_server_id
 * @property integer $s3_access_key_id
 * @property integer $s3_bucket_id
 * @property integer $s3_worm_commitment_id
 * @property string $action
 * @property string $performed_by
 * @property string $reason
 * @property $data
 * @property \Carbon\Carbon $performed_at
 */
class AuditLogs extends Model
{
    use Filterable, UuidId, CleanCache, Taggable, HasStates, RunAsAdministrator, HasObject;

    public $timestamps = false;

    protected $table = 's3_audit_logs';


    /**
     @var array
     */
    protected $guarded = [];

    protected $fillable = [
            'iam_account_id',
            'iam_user_id',
            's3_account_id',
            's3_server_id',
            's3_access_key_id',
            's3_bucket_id',
            's3_worm_commitment_id',
            'action',
            'performed_by',
            'reason',
            'data',
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
    's3_server_id' => 'integer',
    's3_access_key_id' => 'integer',
    's3_bucket_id' => 'integer',
    's3_worm_commitment_id' => 'integer',
    'action' => 'string',
    'performed_by' => 'string',
    'reason' => 'string',
    'data' => 'array',
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
        parent::observe(AuditLogsObserver::class);

        self::registerScopes();
    }

    public static function registerScopes()
    {
        $globalScopes = config('s3.scopes.global');
        $modelScopes = config('s3.scopes.s3_audit_logs');

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
