<?php

namespace NextDeveloper\S3\Http\Controllers\UsageSnapshots;

use Illuminate\Http\Request;
use NextDeveloper\S3\Http\Controllers\AbstractController;
use NextDeveloper\Commons\Http\Response\ResponsableFactory;
use NextDeveloper\S3\Http\Requests\UsageSnapshots\UsageSnapshotsUpdateRequest;
use NextDeveloper\S3\Database\Filters\UsageSnapshotsQueryFilter;
use NextDeveloper\S3\Database\Models\UsageSnapshots;
use NextDeveloper\S3\Services\UsageSnapshotsService;
use NextDeveloper\S3\Http\Requests\UsageSnapshots\UsageSnapshotsCreateRequest;
use NextDeveloper\Commons\Http\Traits\Tags as TagsTrait;use NextDeveloper\Commons\Http\Traits\Addresses as AddressesTrait;
class UsageSnapshotsController extends AbstractController
{
    private $model = UsageSnapshots::class;

    use TagsTrait;
    use AddressesTrait;
    /**
     * This method returns the list of usagesnapshots.
     *
     * optional http params:
     * - paginate: If you set paginate parameter, the result will be returned paginated.
     *
     * @param  UsageSnapshotsQueryFilter $filter  An object that builds search query
     * @param  Request                   $request Laravel request object, this holds all data about request. Automatically populated.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(UsageSnapshotsQueryFilter $filter, Request $request)
    {
        $data = UsageSnapshotsService::get($filter, $request->all());

        return ResponsableFactory::makeResponse($this, $data);
    }

    /**
     * This function returns the list of actions that can be performed on this object.
     *
     * @return void
     */
    public function getActions()
    {
        $data = UsageSnapshotsService::getActions();

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
        $actionId = UsageSnapshotsService::doAction($objectId, $action, request()->all());

        return $this->withArray(
            [
            'action_id' =>  $actionId
            ]
        );
    }

    /**
     * This method receives ID for the related model and returns the item to the client.
     *
     * @param  $usageSnapshotsId
     * @return mixed|null
     * @throws \Laravel\Octane\Exceptions\DdException
     */
    public function show($ref)
    {
        //  Here we are not using Laravel Route Model Binding. Please check routeBinding.md file
        //  in NextDeveloper Platform Project
        $model = UsageSnapshotsService::getByRef($ref);

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
        $objects = UsageSnapshotsService::relatedObjects($ref, $subObject);

        return ResponsableFactory::makeResponse($this, $objects);
    }

    /**
     * This method created UsageSnapshots object on database.
     *
     * @param  UsageSnapshotsCreateRequest $request
     * @return mixed|null
     * @throws \NextDeveloper\Commons\Exceptions\CannotCreateModelException
     */
    public function store(UsageSnapshotsCreateRequest $request)
    {
        if($request->has('validateOnly') && $request->get('validateOnly') == true) {
            return [
                'validation'    =>  'success'
            ];
        }

        $model = UsageSnapshotsService::create($request->validated());

        return ResponsableFactory::makeResponse($this, $model);
    }

    /**
     * This method updates UsageSnapshots object on database.
     *
     * @param  $usageSnapshotsId
     * @param  UsageSnapshotsUpdateRequest $request
     * @return mixed|null
     * @throws \NextDeveloper\Commons\Exceptions\CannotCreateModelException
     */
    public function update($usageSnapshotsId, UsageSnapshotsUpdateRequest $request)
    {
        if($request->has('validateOnly') && $request->get('validateOnly') == true) {
            return [
                'validation'    =>  'success'
            ];
        }

        $model = UsageSnapshotsService::update($usageSnapshotsId, $request->validated());

        return ResponsableFactory::makeResponse($this, $model);
    }

    /**
     * This method updates UsageSnapshots object on database.
     *
     * @param  $usageSnapshotsId
     * @return mixed|null
     * @throws \NextDeveloper\Commons\Exceptions\CannotCreateModelException
     */
    public function destroy($usageSnapshotsId)
    {
        $model = UsageSnapshotsService::delete($usageSnapshotsId);

        return $this->noContent();
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * Daily-bucketed storage_bytes/object_count series for a single account,
     * for graphing usage over time. See
     * UsageSnapshotsService::getDailySeriesForAccount().
     *
     * optional http params:
     * - s3_account_id: defaults to the caller's own S3 account if omitted
     * - from / to: inclusive snapshot_at bounds (any Carbon-parseable string)
     *
     * @param  Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function series(Request $request)
    {
        $series = UsageSnapshotsService::getDailySeriesForAccount(
            $request->get('s3_account_id'),
            $request->get('from'),
            $request->get('to')
        );

        return $this->withArray([
            'data' => $series->map(function ($row) {
                return [
                    'day'           => $row->day,
                    'storage_bytes' => (float) $row->storage_bytes,
                    'object_count'  => (float) $row->object_count,
                ];
            })->values(),
        ]);
    }
}
