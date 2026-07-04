<?php

namespace NextDeveloper\S3\Http\Controllers\BackupAgents;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use NextDeveloper\S3\Http\Controllers\AbstractController;
use NextDeveloper\Commons\Http\Response\ResponsableFactory;
use NextDeveloper\S3\Http\Requests\BackupAgents\BackupAgentsUpdateRequest;
use NextDeveloper\S3\Database\Filters\BackupAgentsQueryFilter;
use NextDeveloper\S3\Database\Models\BackupAgents;
use NextDeveloper\S3\Services\BackupAgentsService;
use NextDeveloper\S3\Http\Requests\BackupAgents\BackupAgentsCreateRequest;
use NextDeveloper\Commons\Http\Traits\Tags as TagsTrait;
use NextDeveloper\Commons\Http\Traits\Addresses as AddressesTrait;

class BackupAgentsController extends AbstractController
{
    private $model = BackupAgents::class;

    use TagsTrait;
    use AddressesTrait;

    /**
     * This method returns the list of backup agents.
     *
     * optional http params:
     * - paginate: If you set paginate parameter, the result will be returned paginated.
     *
     * @param  BackupAgentsQueryFilter $filter  An object that builds search query
     * @param  Request                 $request Laravel request object, this holds all data about request. Automatically populated.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(BackupAgentsQueryFilter $filter, Request $request)
    {
        $data = BackupAgentsService::get($filter, $request->all());

        return ResponsableFactory::makeResponse($this, $data);
    }

    public function getActions()
    {
        $data = BackupAgentsService::getActions();

        return ResponsableFactory::makeResponse($this, $data);
    }

    public function doAction($objectId, $action)
    {
        $actionId = BackupAgentsService::doAction($objectId, $action, request()->all());

        return $this->withArray(
            [
            'action_id' =>  $actionId
            ]
        );
    }

    public function show($ref)
    {
        $model = BackupAgentsService::getByRef($ref);

        return ResponsableFactory::makeResponse($this, $model);
    }

    public function relatedObjects($ref, $subObject)
    {
        $objects = BackupAgentsService::relatedObjects($ref, $subObject);

        return ResponsableFactory::makeResponse($this, $objects);
    }

    /**
     * Issues a registration token for a new backup agent slot.
     *
     * This does NOT create an active agent — it creates a `pending` row with a
     * one-time token that the customer passes to `backup-agent register --token=...`
     * on the machine they want to protect. See BackupAgentsService::create().
     *
     * @param  BackupAgentsCreateRequest $request
     * @return mixed|null
     */
    public function store(BackupAgentsCreateRequest $request)
    {
        if($request->has('validateOnly') && $request->get('validateOnly') == true) {
            return [
                'validation'    =>  'success'
            ];
        }

        $model = BackupAgentsService::create($request->validated());

        return ResponsableFactory::makeResponse($this, $model);
    }

    public function update($backupAgentsId, BackupAgentsUpdateRequest $request)
    {
        if($request->has('validateOnly') && $request->get('validateOnly') == true) {
            return [
                'validation'    =>  'success'
            ];
        }

        $model = BackupAgentsService::update($backupAgentsId, $request->validated());

        return ResponsableFactory::makeResponse($this, $model);
    }

    public function destroy($backupAgentsId)
    {
        BackupAgentsService::delete($backupAgentsId);

        return $this->noContent();
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * POST /backup-agents/register
     *
     * Called by the backup.agent binary on first run, outside IAM auth — this
     * route is registered directly in the host app's routes/api.php inside a
     * `withoutMiddleware($iamMiddleware)` group (same pattern as
     * App\Http\Controllers\ManagedServices\AgentRegistrationController), not
     * in this package's api.routes.php, since that file is mounted under the
     * IAM-authenticated 's3' prefix. Auth here is the one-time
     * registration_token in the body, not a bearer/session token.
     */
    public function register(Request $request): JsonResponse
    {
        $result = BackupAgentsService::register(
            $request->input('token'),
            $request->only(['hostname', 'os', 'arch', 'machine_fingerprint', 'agent_version'])
        );

        return response()->json($result, 201);
    }

    /**
     * POST /s3/backup-agents/{s3_backup_agents}/revoke
     *
     * Customer-triggered (IAM-authenticated): tells the agent to shut down and
     * invalidates its NATS credential so it is rejected on next reconnect.
     */
    public function revoke($ref)
    {
        $model = BackupAgentsService::revoke($ref, request()->input('reason', ''));

        return ResponsableFactory::makeResponse($this, $model);
    }
}
