<?php

namespace NextDeveloper\S3\Database\Models;

use Illuminate\Database\Eloquent\Model;
use NextDeveloper\Commons\Database\Traits\Filterable;
use NextDeveloper\S3\Database\Observers\RestoreJobsObserver;
use NextDeveloper\Commons\Database\Traits\UuidId;
use NextDeveloper\Commons\Database\Traits\HasObject;
use NextDeveloper\Commons\Common\Cache\Traits\CleanCache;
use NextDeveloper\Commons\Database\Traits\RunAsAdministrator;

/**
 * RestoreJobs model.
 *
 * One row per customer-initiated restore of backup data back to a real
 * destination on the agent's machine. Rows are created by
 * BackupAgentCommandService/BackupAgentEventService only; there is no public
 * create/update endpoint (see RestoreJobsController).
 *
 * @package  NextDeveloper\S3\Database\Models
 * @property integer $id
 * @property string $uuid
 * @property integer $s3_backup_job_id
 * @property integer $s3_backup_job_run_id
 * @property integer $iam_account_id
 * @property integer $iam_user_id
 * @property string $destination_path
 * @property array $restore_paths
 * @property string $status
 * @property boolean $verified
 * @property integer $bytes_restored
 * @property string $error
 * @property string $triggered_by
 * @property \Carbon\Carbon $started_at
 * @property \Carbon\Carbon $finished_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class RestoreJobs extends Model
{
    use Filterable, UuidId, CleanCache, RunAsAdministrator, HasObject;

    public $timestamps = true;

    protected $table = 's3_restore_jobs';

    /**
     @var array
     */
    protected $guarded = [];

    protected $fillable = [
            's3_backup_job_id',
            's3_backup_job_run_id',
            'iam_account_id',
            'iam_user_id',
            'destination_path',
            'restore_paths',
            'status',
            'verified',
            'bytes_restored',
            'error',
            'triggered_by',
            'started_at',
            'finished_at',
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
    's3_backup_job_id' => 'integer',
    's3_backup_job_run_id' => 'integer',
    'iam_account_id' => 'integer',
    'iam_user_id' => 'integer',
    'destination_path' => 'string',
    'restore_paths' => \NextDeveloper\Commons\Database\Casts\TextArray::class,
    'status' => 'string',
    'verified' => 'boolean',
    'bytes_restored' => 'integer',
    'error' => 'string',
    'triggered_by' => 'string',
    'started_at' => 'datetime',
    'finished_at' => 'datetime',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    ];

    /**
     We are casting data fields.
     *
     @var array
     */
    protected $dates = [
    'started_at',
    'finished_at',
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
        parent::observe(RestoreJobsObserver::class);

        self::registerScopes();
    }

    public static function registerScopes()
    {
        $globalScopes = config('s3.scopes.global');
        $modelScopes = config('s3.scopes.s3_restore_jobs');

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

    public function backupJob() : \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\NextDeveloper\S3\Database\Models\BackupJobs::class, 's3_backup_job_id');
    }

    public function backupJobRun() : \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\NextDeveloper\S3\Database\Models\BackupJobRuns::class, 's3_backup_job_run_id');
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
