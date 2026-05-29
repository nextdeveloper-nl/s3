<?php

namespace NextDeveloper\S3\Http\Controllers\BandwidthMonthlies;

use Illuminate\Http\Request;
use NextDeveloper\S3\Http\Controllers\AbstractController;
use NextDeveloper\Commons\Http\Response\ResponsableFactory;
use NextDeveloper\S3\Http\Requests\BandwidthMonthlies\BandwidthMonthliesUpdateRequest;
use NextDeveloper\S3\Database\Filters\BandwidthMonthliesQueryFilter;
use NextDeveloper\S3\Database\Models\BandwidthMonthlies;
use NextDeveloper\S3\Services\BandwidthMonthliesService;
use NextDeveloper\S3\Http\Requests\BandwidthMonthlies\BandwidthMonthliesCreateRequest;
use NextDeveloper\Commons\Http\Traits\Tags as TagsTrait;use NextDeveloper\Commons\Http\Traits\Addresses as AddressesTrait;
class BandwidthMonthliesController extends AbstractController
{
    private $model = BandwidthMonthlies::class;

    use TagsTrait;
    use AddressesTrait;
    /**
     * This method returns the list of bandwidthmonthlies.
     *
     * optional http params:
     * - paginate: If you set paginate parameter, the result will be returned paginated.
     *
     * @param  BandwidthMonthliesQueryFilter $filter  An object that builds search query
     * @param  Request                       $request Laravel request object, this holds all data about request. Automatically populated.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(BandwidthMonthliesQueryFilter $filter, Request $request)
    {
        $data = BandwidthMonthliesService::get($filter, $request->all());

        return ResponsableFactory::makeResponse($this, $data);
    }

    /**
     * This function returns the list of actions that can be performed on this object.
     *
     * @return void
     */
    public function getActions()
    {
        $data = BandwidthMonthliesService::getActions();

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
        $actionId = BandwidthMonthliesService::doAction($objectId, $action, request()->all());

        return $this->withArray(
            [
            'action_id' =>  $actionId
            ]
        );
    }

    /**
     * This method receives ID for the related model and returns the item to the client.
     *
     * @param  $bandwidthMonthliesId
     * @return mixed|null
     * @throws \Laravel\Octane\Exceptions\DdException
     */
    public function show($ref)
    {
        //  Here we are not using Laravel Route Model Binding. Please check routeBinding.md file
        //  in NextDeveloper Platform Project
        $model = BandwidthMonthliesService::getByRef($ref);

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
        $objects = BandwidthMonthliesService::relatedObjects($ref, $subObject);

        return ResponsableFactory::makeResponse($this, $objects);
    }

    /**
     * This method created BandwidthMonthlies object on database.
     *
     * @param  BandwidthMonthliesCreateRequest $request
     * @return mixed|null
     * @throws \NextDeveloper\Commons\Exceptions\CannotCreateModelException
     */
    public function store(BandwidthMonthliesCreateRequest $request)
    {
        if($request->has('validateOnly') && $request->get('validateOnly') == true) {
            return [
                'validation'    =>  'success'
            ];
        }

        $model = BandwidthMonthliesService::create($request->validated());

        return ResponsableFactory::makeResponse($this, $model);
    }

    /**
     * This method updates BandwidthMonthlies object on database.
     *
     * @param  $bandwidthMonthliesId
     * @param  BandwidthMonthliesUpdateRequest $request
     * @return mixed|null
     * @throws \NextDeveloper\Commons\Exceptions\CannotCreateModelException
     */
    public function update($bandwidthMonthliesId, BandwidthMonthliesUpdateRequest $request)
    {
        if($request->has('validateOnly') && $request->get('validateOnly') == true) {
            return [
                'validation'    =>  'success'
            ];
        }

        $model = BandwidthMonthliesService::update($bandwidthMonthliesId, $request->validated());

        return ResponsableFactory::makeResponse($this, $model);
    }

    /**
     * This method updates BandwidthMonthlies object on database.
     *
     * @param  $bandwidthMonthliesId
     * @return mixed|null
     * @throws \NextDeveloper\Commons\Exceptions\CannotCreateModelException
     */
    public function destroy($bandwidthMonthliesId)
    {
        $model = BandwidthMonthliesService::delete($bandwidthMonthliesId);

        return $this->noContent();
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

}
