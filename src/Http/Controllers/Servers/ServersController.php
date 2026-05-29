<?php

namespace NextDeveloper\S3\Http\Controllers\Servers;

use Illuminate\Http\Request;
use NextDeveloper\S3\Http\Controllers\AbstractController;
use NextDeveloper\Commons\Http\Response\ResponsableFactory;
use NextDeveloper\S3\Http\Requests\Servers\ServersUpdateRequest;
use NextDeveloper\S3\Database\Filters\ServersQueryFilter;
use NextDeveloper\S3\Database\Models\Servers;
use NextDeveloper\S3\Services\AuditLogsService;
use NextDeveloper\S3\Services\ServersService;
use NextDeveloper\S3\Services\ServerTelemetriesService;
use NextDeveloper\S3\Http\Requests\Servers\ServersCreateRequest;
use NextDeveloper\Commons\Http\Traits\Tags as TagsTrait;use NextDeveloper\Commons\Http\Traits\Addresses as AddressesTrait;
class ServersController extends AbstractController
{
    private $model = Servers::class;

    use TagsTrait;
    use AddressesTrait;
    /**
     * This method returns the list of servers.
     *
     * optional http params:
     * - paginate: If you set paginate parameter, the result will be returned paginated.
     *
     * @param  ServersQueryFilter $filter  An object that builds search query
     * @param  Request            $request Laravel request object, this holds all data about request. Automatically populated.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(ServersQueryFilter $filter, Request $request)
    {
        $data = ServersService::get($filter, $request->all());

        return ResponsableFactory::makeResponse($this, $data);
    }

    /**
     * This function returns the list of actions that can be performed on this object.
     *
     * @return void
     */
    public function getActions()
    {
        $data = ServersService::getActions();

        return ResponsableFactory::makeResponse($this, $data);
    }

    /**
     * Makes the related action to the object
     *
     * @param  $objectId
     * @param  $action
     * @return array
     */
    public function doAction($objectId, $action)
    {
        $actionId = ServersService::doAction($objectId, $action, request()->all());

        return $this->withArray(
            [
            'action_id' =>  $actionId
            ]
        );
    }

    /**
     * This method receives ID for the related model and returns the item to the client.
     *
     * @param  $serversId
     * @return mixed|null
     * @throws \Laravel\Octane\Exceptions\DdException
     */
    public function show($ref)
    {
        //  Here we are not using Laravel Route Model Binding. Please check routeBinding.md file
        //  in NextDeveloper Platform Project
        $model = ServersService::getByRef($ref);

        return ResponsableFactory::makeResponse($this, $model);
    }

    /**
     * This method returns the list of sub objects the related object. Sub object means an object which is preowned by
     * this object.
     *
     * It can be tags, addresses, states etc.
     *
     * @param  $ref
     * @param  $subObject
     * @return void
     */
    public function relatedObjects($ref, $subObject)
    {
        $objects = ServersService::relatedObjects($ref, $subObject);

        return ResponsableFactory::makeResponse($this, $objects);
    }

    /**
     * This method created Servers object on database.
     *
     * @param  ServersCreateRequest $request
     * @return mixed|null
     * @throws \NextDeveloper\Commons\Exceptions\CannotCreateModelException
     */
    public function store(ServersCreateRequest $request)
    {
        if($request->has('validateOnly') && $request->get('validateOnly') == true) {
            return [
                'validation'    =>  'success'
            ];
        }

        $model = ServersService::create($request->validated());

        return ResponsableFactory::makeResponse($this, $model);
    }

    /**
     * This method updates Servers object on database.
     *
     * @param  $serversId
     * @param  ServersUpdateRequest $request
     * @return mixed|null
     * @throws \NextDeveloper\Commons\Exceptions\CannotCreateModelException
     */
    public function update($serversId, ServersUpdateRequest $request)
    {
        if($request->has('validateOnly') && $request->get('validateOnly') == true) {
            return [
                'validation'    =>  'success'
            ];
        }

        $model = ServersService::update($serversId, $request->validated());

        return ResponsableFactory::makeResponse($this, $model);
    }

    /**
     * This method updates Servers object on database.
     *
     * @param  $serversId
     * @return mixed|null
     * @throws \NextDeveloper\Commons\Exceptions\CannotCreateModelException
     */
    public function destroy($serversId)
    {
        $model = ServersService::delete($serversId);

        return $this->noContent();
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * Receive a 30-second telemetry snapshot from the storaged agent.
     *
     * Called by the Go agent every 30 seconds with server health metrics.
     *
     * @param  Request  $request  JSON body: master_reachable, volume_count, volumes_degraded,
     *                            capacity_bytes_total, capacity_bytes_used, agent_version, etc.
     * @param  string   $ref      Server UUID
     */
    public function telemetry(Request $request, string $ref)
    {
        $server = ServersService::getByRef($ref);

        if (!$server) {
            return response()->json(['error' => 'Server not found'], 404);
        }

        ServerTelemetriesService::ingest($ref, $request->all());

        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * SSE stream for commands dispatched to the storaged agent.
     *
     * The Go agent connects here and receives commands (full_sync, bucket_create, etc.)
     * via Server-Sent Events. Not yet implemented — returns 501 until the agent is built.
     *
     * @param  string  $ref  Server UUID
     */
    public function events(string $ref)
    {
        // TODO: Implement SSE command stream when storaged Go agent is ready
        return response()->json([
            'error'   => 'Not implemented',
            'message' => 'SSE command stream for storaged agent is pending Go agent implementation.',
        ], 501);
    }

    /**
     * Receive a command acknowledgment from the storaged agent.
     *
     * @param  Request  $request  JSON body: command_id, status, error (optional)
     * @param  string   $ref      Server UUID
     */
    public function ack(Request $request, string $ref)
    {
        $server = ServersService::getByRef($ref);

        if (!$server) {
            return response()->json(['error' => 'Server not found'], 404);
        }

        AuditLogsService::log('agent.ack', 'agent', [
            's3_server_id' => $server->id,
            'command_id'   => $request->get('command_id'),
            'status'       => $request->get('status'),
            'error'        => $request->get('error'),
        ]);

        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * Accept an immediate state change PATCH from the storaged agent.
     *
     * Triggered when a significant health event occurs (component reachability flip,
     * capacity threshold crossed, degraded volumes change) without waiting for the
     * next 30-second telemetry tick.
     *
     * @param  Request  $request  JSON body: same shape as telemetry
     * @param  string   $ref      Server UUID
     */
    public function state(Request $request, string $ref)
    {
        $server = ServersService::getByRef($ref);

        if (!$server) {
            return response()->json(['error' => 'Server not found'], 404);
        }

        ServersService::updateHealthFromTelemetry($ref, $request->all());

        return response()->json(['status' => 'ok'], 200);
    }
}
