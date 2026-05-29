<?php

namespace NextDeveloper\S3\Http\Controllers\ServerTelemetries;

use Illuminate\Http\Request;
use NextDeveloper\S3\Http\Controllers\AbstractController;
use NextDeveloper\Commons\Http\Response\ResponsableFactory;
use NextDeveloper\S3\Http\Requests\ServerTelemetries\ServerTelemetriesUpdateRequest;
use NextDeveloper\S3\Database\Filters\ServerTelemetriesQueryFilter;
use NextDeveloper\S3\Database\Models\ServerTelemetries;
use NextDeveloper\S3\Services\ServerTelemetriesService;
use NextDeveloper\S3\Http\Requests\ServerTelemetries\ServerTelemetriesCreateRequest;
use NextDeveloper\Commons\Http\Traits\Tags as TagsTrait;use NextDeveloper\Commons\Http\Traits\Addresses as AddressesTrait;
class ServerTelemetriesController extends AbstractController
{
    private $model = ServerTelemetries::class;

    use TagsTrait;
    use AddressesTrait;
    /**
     * This method returns the list of servertelemetries.
     *
     * optional http params:
     * - paginate: If you set paginate parameter, the result will be returned paginated.
     *
     * @param  ServerTelemetriesQueryFilter $filter  An object that builds search query
     * @param  Request                      $request Laravel request object, this holds all data about request. Automatically populated.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(ServerTelemetriesQueryFilter $filter, Request $request)
    {
        $data = ServerTelemetriesService::get($filter, $request->all());

        return ResponsableFactory::makeResponse($this, $data);
    }

    /**
     * This function returns the list of actions that can be performed on this object.
     *
     * @return void
     */
    public function getActions()
    {
        $data = ServerTelemetriesService::getActions();

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
        $actionId = ServerTelemetriesService::doAction($objectId, $action, request()->all());

        return $this->withArray(
            [
            'action_id' =>  $actionId
            ]
        );
    }

    /**
     * This method receives ID for the related model and returns the item to the client.
     *
     * @param  $serverTelemetriesId
     * @return mixed|null
     * @throws \Laravel\Octane\Exceptions\DdException
     */
    public function show($ref)
    {
        //  Here we are not using Laravel Route Model Binding. Please check routeBinding.md file
        //  in NextDeveloper Platform Project
        $model = ServerTelemetriesService::getByRef($ref);

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
        $objects = ServerTelemetriesService::relatedObjects($ref, $subObject);

        return ResponsableFactory::makeResponse($this, $objects);
    }

    /**
     * This method created ServerTelemetries object on database.
     *
     * @param  ServerTelemetriesCreateRequest $request
     * @return mixed|null
     * @throws \NextDeveloper\Commons\Exceptions\CannotCreateModelException
     */
    public function store(ServerTelemetriesCreateRequest $request)
    {
        if($request->has('validateOnly') && $request->get('validateOnly') == true) {
            return [
                'validation'    =>  'success'
            ];
        }

        $model = ServerTelemetriesService::create($request->validated());

        return ResponsableFactory::makeResponse($this, $model);
    }

    /**
     * This method updates ServerTelemetries object on database.
     *
     * @param  $serverTelemetriesId
     * @param  ServerTelemetriesUpdateRequest $request
     * @return mixed|null
     * @throws \NextDeveloper\Commons\Exceptions\CannotCreateModelException
     */
    public function update($serverTelemetriesId, ServerTelemetriesUpdateRequest $request)
    {
        if($request->has('validateOnly') && $request->get('validateOnly') == true) {
            return [
                'validation'    =>  'success'
            ];
        }

        $model = ServerTelemetriesService::update($serverTelemetriesId, $request->validated());

        return ResponsableFactory::makeResponse($this, $model);
    }

    /**
     * This method updates ServerTelemetries object on database.
     *
     * @param  $serverTelemetriesId
     * @return mixed|null
     * @throws \NextDeveloper\Commons\Exceptions\CannotCreateModelException
     */
    public function destroy($serverTelemetriesId)
    {
        $model = ServerTelemetriesService::delete($serverTelemetriesId);

        return $this->noContent();
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

}
