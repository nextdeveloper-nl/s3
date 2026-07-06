<?php

namespace NextDeveloper\S3\Database\Observers;

use Illuminate\Database\Eloquent\Model;
use NextDeveloper\Events\Services\Events;

/**
 * Class RestoreJobsObserver
 *
 * Like BackupJobRunsObserver, this does not gate creating/updating behind
 * UserHelper::can() — rows are written by BackupAgentCommandService and
 * BackupAgentEventService acting on the agent's behalf (via
 * UserHelper::runAsAdmin in the event-handling path), not by an end user
 * directly writing to this model.
 *
 * @package NextDeveloper\S3\Database\Observers
 */
class RestoreJobsObserver
{
    public function retrieved(Model $model)
    {

    }

    public function creating(Model $model)
    {
        Events::fire('creating:NextDeveloper\S3\RestoreJobs', $model);
    }

    public function created(Model $model)
    {
        Events::fire('created:NextDeveloper\S3\RestoreJobs', $model);
    }

    public function saving(Model $model)
    {
        Events::fire('saving:NextDeveloper\S3\RestoreJobs', $model);
    }

    public function saved(Model $model)
    {
        Events::fire('saved:NextDeveloper\S3\RestoreJobs', $model);
    }

    public function updating(Model $model)
    {
        Events::fire('updating:NextDeveloper\S3\RestoreJobs', $model);
    }

    public function updated(Model $model)
    {
        Events::fire('updated:NextDeveloper\S3\RestoreJobs', $model);
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
