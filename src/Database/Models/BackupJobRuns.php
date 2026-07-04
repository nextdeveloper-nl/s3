<?php

namespace NextDeveloper\S3\Database\Models;

use Illuminate\Database\Eloquent\Model;
use NextDeveloper\Commons\Database\Traits\Filterable;
use NextDeveloper\S3\Database\Observers\BackupJobRunsObserver;
use NextDeveloper\Commons\Database\Traits\UuidId;
use NextDeveloper\Commons\Database\Traits\HasObject;
use NextDeveloper\Commons\Common\Cache\Traits\CleanCache;
use NextDeveloper\Commons\Database\Traits\RunAsAdministrator;

/**
 * BackupJobRuns model.
 *
 * One row per execution of a BackupJobs job. This is the source of truth for
 * "did the backup actually work" — never inferred from a heartbeat alone.
 * Rows are created by BackupAgentCommandService/BackupAgentEventService only;
 * there is no public create/update endpoint (see BackupJobRunsController).
 *
 * @package  NextDeveloper\S3\Database\Models
 * @property integer $id
 * @property string $uuid
 * @property integer $s3_backup_job_id
 * @property string $status
 * @property \Carbon\Carbon $started_at
 * @property \Carbon\Carbon $finished_at
 * @property integer $bytes_uploaded
 * @property integer $bytes_deduped
 * @property string $kopia_snapshot_id
 * @property string $error
 * @property string $triggered_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class BackupJobRuns extends Model
{
    use Filterable, UuidId, CleanCache, RunAsAdministrator, HasObject;

    public $timestamps = true;

    protected $table = 's3_backup_job_runs';

    /**
     @var array
     */
    protected $guarded = [];

    protected $fillable = [
            's3_backup_job_id',
            'status',
            'started_at',
            'finished_at',
            'bytes_uploaded',
            'bytes_deduped',
            'kopia_snapshot_id',
            'error',
            'triggered_by',
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
    'status' => 'string',
    'started_at' => 'datetime',
    'finished_at' => 'datetime',
    'bytes_uploaded' => 'integer',
    'bytes_deduped' => 'integer',
    'kopia_snapshot_id' => 'string',
    'error' => 'string',
    'triggered_by' => 'string',
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
        parent::observe(BackupJobRunsObserver::class);

        self::registerScopes();
    }

    public static function registerScopes()
    {
        $globalScopes = config('s3.scopes.global');
        $modelScopes = config('s3.scopes.s3_backup_job_runs');

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

    public function job() : \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\NextDeveloper\S3\Database\Models\BackupJobs::class, 's3_backup_job_id');
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
