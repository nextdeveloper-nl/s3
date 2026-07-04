<?php

namespace NextDeveloper\S3\Database\Observers;

use Illuminate\Database\Eloquent\Model;
use NextDeveloper\Events\Services\Events;

/**
 * Class BackupJobRunsObserver
 *
 * Unlike the other backup observers, this one does not gate creating/updating
 * behind UserHelper::can() — runs are written by BackupAgentCommandService and
 * BackupAgentEventService acting on the agent's behalf (via UserHelper::runAsAdmin),
 * not by an end user through the API.
 *
 * @package NextDeveloper\S3\Database\Observers
 */
class BackupJobRunsObserver
{
    public function retrieved(Model $model)
    {

    }

    public function creating(Model $model)
    {
        Events::fire('creating:NextDeveloper\S3\BackupJobRuns', $model);
    }

    public function created(Model $model)
    {
        Events::fire('created:NextDeveloper\S3\BackupJobRuns', $model);
    }

    public function saving(Model $model)
    {
        Events::fire('saving:NextDeveloper\S3\BackupJobRuns', $model);
    }

    public function saved(Model $model)
    {
        Events::fire('saved:NextDeveloper\S3\BackupJobRuns', $model);
    }

    public function updating(Model $model)
    {
        Events::fire('updating:NextDeveloper\S3\BackupJobRuns', $model);
    }

    public function updated(Model $model)
    {
        Events::fire('updated:NextDeveloper\S3\BackupJobRuns', $model);
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
