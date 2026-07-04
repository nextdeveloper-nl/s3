<?php

namespace NextDeveloper\S3\Database\Observers;

use Illuminate\Database\Eloquent\Model;
use NextDeveloper\Commons\Exceptions\NotAllowedException;
use NextDeveloper\IAM\Helpers\UserHelper;
use NextDeveloper\Events\Services\Events;

/**
 * Class BackupAgentsObserver
 *
 * @package NextDeveloper\S3\Database\Observers
 */
class BackupAgentsObserver
{
    public function retrieved(Model $model)
    {

    }

    public function creating(Model $model)
    {
        throw_if(
            !UserHelper::can('create', $model),
            new NotAllowedException('You are not allowed to create this record')
        );

        Events::fire('creating:NextDeveloper\S3\BackupAgents', $model);
    }

    public function created(Model $model)
    {
        Events::fire('created:NextDeveloper\S3\BackupAgents', $model);
    }

    public function saving(Model $model)
    {
        throw_if(
            !UserHelper::can('save', $model),
            new NotAllowedException('You are not allowed to save this record')
        );

        Events::fire('saving:NextDeveloper\S3\BackupAgents', $model);
    }

    public function saved(Model $model)
    {
        Events::fire('saved:NextDeveloper\S3\BackupAgents', $model);
    }

    public function updating(Model $model)
    {
        throw_if(
            !UserHelper::can('update', $model),
            new NotAllowedException('You are not allowed to update this record')
        );

        Events::fire('updating:NextDeveloper\S3\BackupAgents', $model);
    }

    public function updated(Model $model)
    {
        Events::fire('updated:NextDeveloper\S3\BackupAgents', $model);
    }

    public function deleting(Model $model)
    {
        throw_if(
            !UserHelper::can('delete', $model),
            new NotAllowedException('You are not allowed to delete this record')
        );

        Events::fire('deleting:NextDeveloper\S3\BackupAgents', $model);
    }

    public function deleted(Model $model)
    {
        Events::fire('deleted:NextDeveloper\S3\BackupAgents', $model);
    }

    public function restoring(Model $model)
    {
        throw_if(
            !UserHelper::can('restore', $model),
            new NotAllowedException('You are not allowed to restore this record')
        );

        Events::fire('restoring:NextDeveloper\S3\BackupAgents', $model);
    }

    public function restored(Model $model)
    {
        Events::fire('restored:NextDeveloper\S3\BackupAgents', $model);
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
