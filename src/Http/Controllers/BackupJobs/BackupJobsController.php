<?php

namespace NextDeveloper\S3\Http\Controllers\BackupJobs;

use Illuminate\Http\Request;
use NextDeveloper\S3\Http\Controllers\AbstractController;
use NextDeveloper\Commons\Http\Response\ResponsableFactory;
use NextDeveloper\S3\Http\Requests\BackupJobs\BackupJobsUpdateRequest;
use NextDeveloper\S3\Database\Filters\BackupJobsQueryFilter;
use NextDeveloper\S3\Database\Models\BackupJobs;
use NextDeveloper\S3\Services\BackupJobsService;
use NextDeveloper\S3\Services\BackupAgentCommandService;
use NextDeveloper\S3\Http\Requests\BackupJobs\BackupJobsCreateRequest;
use NextDeveloper\Commons\Http\Traits\Tags as TagsTrait;
use NextDeveloper\Commons\Http\Traits\Addresses as AddressesTrait;

class BackupJobsController extends AbstractController
{
    private $model = BackupJobs::class;

    use TagsTrait;
    use AddressesTrait;

    public function index(BackupJobsQueryFilter $filter, Request $request)
    {
        $data = BackupJobsService::get($filter, $request->all());

        return ResponsableFactory::makeResponse($this, $data);
    }

    public function getActions()
    {
        $data = BackupJobsService::getActions();

        return ResponsableFactory::makeResponse($this, $data);
    }

    public function doAction($objectId, $action)
    {
        $actionId = BackupJobsService::doAction($objectId, $action, request()->all());

        return $this->withArray(
            [
            'action_id' =>  $actionId
            ]
        );
    }

    public function show($ref)
    {
        $model = BackupJobsService::getByRef($ref);

        return ResponsableFactory::makeResponse($this, $model);
    }

    public function relatedObjects($ref, $subObject)
    {
        $objects = BackupJobsService::relatedObjects($ref, $subObject);

        return ResponsableFactory::makeResponse($this, $objects);
    }

    public function store(BackupJobsCreateRequest $request)
    {
        if($request->has('validateOnly') && $request->get('validateOnly') == true) {
            return [
                'validation'    =>  'success'
            ];
        }

        $model = BackupJobsService::create($request->validated());

        return ResponsableFactory::makeResponse($this, $model);
    }

    public function update($backupJobsId, BackupJobsUpdateRequest $request)
    {
        if($request->has('validateOnly') && $request->get('validateOnly') == true) {
            return [
                'validation'    =>  'success'
            ];
        }

        $model = BackupJobsService::update($backupJobsId, $request->validated());

        return ResponsableFactory::makeResponse($this, $model);
    }

    public function destroy($backupJobsId)
    {
        BackupJobsService::delete($backupJobsId);

        return $this->noContent();
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * POST /s3/backup-jobs/{s3_backup_jobs}/run-now
     *
     * Dispatches a run_job_now command to the owning agent immediately,
     * outside its normal schedule. Creates the pending BackupJobRuns row too —
     * see BackupAgentCommandService::runJobNow().
     */
    public function runNow($ref)
    {
        $model = BackupJobsService::getByRef($ref);

        $run = BackupAgentCommandService::runJobNow($model);

        return ResponsableFactory::makeResponse($this, $run);
    }
}
