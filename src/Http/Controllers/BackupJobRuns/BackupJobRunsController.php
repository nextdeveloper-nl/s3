<?php

namespace NextDeveloper\S3\Http\Controllers\BackupJobRuns;

use Illuminate\Http\Request;
use NextDeveloper\S3\Http\Controllers\AbstractController;
use NextDeveloper\Commons\Http\Response\ResponsableFactory;
use NextDeveloper\S3\Database\Filters\BackupJobRunsQueryFilter;
use NextDeveloper\S3\Database\Models\BackupJobRuns;
use NextDeveloper\S3\Services\BackupJobRunsService;

/**
 * Read-only on purpose: runs are written by BackupAgentCommandService and
 * BackupAgentEventService as commands are dispatched and results/telemetry
 * come back over NATS — not by a customer submitting a form. This is the
 * "last successful backup" view for the dashboard.
 */
class BackupJobRunsController extends AbstractController
{
    private $model = BackupJobRuns::class;

    public function index(BackupJobRunsQueryFilter $filter, Request $request)
    {
        $data = BackupJobRunsService::get($filter, $request->all());

        return ResponsableFactory::makeResponse($this, $data);
    }

    public function show($ref)
    {
        $model = BackupJobRunsService::getByRef($ref);

        return ResponsableFactory::makeResponse($this, $model);
    }

    public function relatedObjects($ref, $subObject)
    {
        $objects = BackupJobRunsService::relatedObjects($ref, $subObject);

        return ResponsableFactory::makeResponse($this, $objects);
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
