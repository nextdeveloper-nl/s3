<?php

namespace NextDeveloper\S3\Database\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use NextDeveloper\Commons\Database\Traits\HasStates;
use Illuminate\Database\Eloquent\Model;
use NextDeveloper\Commons\Database\Traits\Filterable;
use NextDeveloper\S3\Database\Observers\BackupJobsObserver;
use NextDeveloper\Commons\Database\Traits\UuidId;
use NextDeveloper\Commons\Database\Traits\HasObject;
use NextDeveloper\Commons\Common\Cache\Traits\CleanCache;
use NextDeveloper\Commons\Database\Traits\Taggable;
use NextDeveloper\Commons\Database\Traits\RunAsAdministrator;

/**
 * BackupJobs model.
 *
 * A named backup job belonging to one BackupAgents row. Either backs up raw
 * file/folder paths directly (job_type=files), or runs a script first and
 * backs up that script's output (job_type=script, e.g. a mysqldump wrapper).
 * The schedule is evaluated agent-side, not platform-triggered — the
 * customer's machine may be offline/asleep on a platform-driven tick.
 *
 * @package  NextDeveloper\S3\Database\Models
 * @property integer $id
 * @property string $uuid
 * @property integer $s3_backup_agent_id
 * @property integer $s3_bucket_id
 * @property integer $iam_account_id
 * @property integer $iam_user_id
 * @property string $name
 * @property string $job_type
 * @property array $source_paths
 * @property string $pre_script
 * @property integer $script_timeout_s
 * @property string $schedule
 * @property integer $keep_last_n
 * @property integer $keep_for_days
 * @property boolean $is_enabled
 * @property integer $bandwidth_limit_mbps
 * @property boolean $object_lock_enabled
 * @property string $status
 * @property array $tags
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 */
class BackupJobs extends Model
{
    use Filterable, UuidId, CleanCache, Taggable, HasStates, RunAsAdministrator, HasObject;
    use SoftDeletes;

    public $timestamps = true;

    protected $table = 's3_backup_jobs';

    /**
     @var array
     */
    protected $guarded = [];

    protected $fillable = [
            's3_backup_agent_id',
            's3_bucket_id',
            'iam_account_id',
            'iam_user_id',
            'name',
            'job_type',
            'source_paths',
            'pre_script',
            'script_timeout_s',
            'schedule',
            'keep_last_n',
            'keep_for_days',
            'is_enabled',
            'bandwidth_limit_mbps',
            'object_lock_enabled',
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
    's3_backup_agent_id' => 'integer',
    's3_bucket_id' => 'integer',
    'name' => 'string',
    'job_type' => 'string',
    'source_paths' => \NextDeveloper\Commons\Database\Casts\TextArray::class,
    'pre_script' => 'string',
    'script_timeout_s' => 'integer',
    'schedule' => 'string',
    'keep_last_n' => 'integer',
    'keep_for_days' => 'integer',
    'is_enabled' => 'boolean',
    'bandwidth_limit_mbps' => 'integer',
    'object_lock_enabled' => 'boolean',
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
        parent::observe(BackupJobsObserver::class);

        self::registerScopes();
    }

    public static function registerScopes()
    {
        $globalScopes = config('s3.scopes.global');
        $modelScopes = config('s3.scopes.s3_backup_jobs');

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

    public function agent() : \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\NextDeveloper\S3\Database\Models\BackupAgents::class, 's3_backup_agent_id');
    }

    public function bucket() : \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\NextDeveloper\S3\Database\Models\Buckets::class, 's3_bucket_id');
    }

    public function runs() : \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\NextDeveloper\S3\Database\Models\BackupJobRuns::class);
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
