<?php

namespace NextDeveloper\S3\Http\Controllers\RestoreJobs;

use Illuminate\Http\Request;
use NextDeveloper\S3\Http\Controllers\AbstractController;
use NextDeveloper\Commons\Http\Response\ResponsableFactory;
use NextDeveloper\S3\Database\Filters\RestoreJobsQueryFilter;
use NextDeveloper\S3\Database\Models\RestoreJobs;
use NextDeveloper\S3\Services\RestoreJobsService;

/**
 * Read-only on purpose: restore jobs are written by BackupAgentCommandService
 * and BackupAgentEventService as the restore command is dispatched and the
 * result comes back over NATS — not by a customer submitting a form. Triggering
 * a restore happens via BackupJobsController::restore(); this is the
 * restore-history view.
 */
class RestoreJobsController extends AbstractController
{
    private $model = RestoreJobs::class;

    public function index(RestoreJobsQueryFilter $filter, Request $request)
    {
        $data = RestoreJobsService::get($filter, $request->all());

        return ResponsableFactory::makeResponse($this, $data);
    }

    public function show($ref)
    {
        $model = RestoreJobsService::getByRef($ref);

        return ResponsableFactory::makeResponse($this, $model);
    }

    public function relatedObjects($ref, $subObject)
    {
        $objects = RestoreJobsService::relatedObjects($ref, $subObject);

        return ResponsableFactory::makeResponse($this, $objects);
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
