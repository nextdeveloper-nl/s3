<?php

namespace NextDeveloper\S3\Services\AbstractServices;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use NextDeveloper\IAM\Helpers\UserHelper;
use NextDeveloper\Commons\Helpers\DatabaseHelper;
use NextDeveloper\Commons\Database\Models\AvailableActions;
use NextDeveloper\S3\Database\Models\BackupAgents;
use NextDeveloper\S3\Database\Filters\BackupAgentsQueryFilter;
use NextDeveloper\Commons\Exceptions\ModelNotFoundException;
use NextDeveloper\Commons\Exceptions\NotAllowedException;

/**
 * This class is responsible from managing the data for BackupAgents
 *
 * Class AbstractBackupAgentsService.
 *
 * @package NextDeveloper\S3\Database\Models
 */
class AbstractBackupAgentsService
{
    public static function get(?BackupAgentsQueryFilter $filter = null, array $params = []) : Collection|LengthAwarePaginator
    {
        $enablePaginate = array_key_exists('paginate', $params);

        $request = new Request();

        if($filter == null) {
            $filter = new BackupAgentsQueryFilter($request);
        }

        $perPage = config('commons.pagination.per_page');

        if($perPage == null) {
            $perPage = 20;
        }

        if(array_key_exists('per_page', $params)) {
            $perPage = intval($params['per_page']);

            if($perPage == 0) {
                $perPage = 20;
            }
        }

        if(array_key_exists('orderBy', $params)) {
            $filter->orderBy($params['orderBy']);
        }

        $model = BackupAgents::filter($filter);

        if($enablePaginate) {
            $modelCount = $model->count();
            $page = array_key_exists('page', $params) ? $params['page'] : 1;
            $items = $model->skip(($page - 1) * $perPage)->take($perPage)->get();

            return new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $modelCount,
                $perPage,
                $page
            );
        }

        return $model->get();
    }

    public static function getAll()
    {
        return BackupAgents::all();
    }

    public static function getByRef($ref) : ?BackupAgents
    {
        return BackupAgents::findByRef($ref);
    }

    public static function getActions()
    {
        $model = BackupAgents::class;

        $model = Str::remove('Database\\Models\\', $model);

        $actions = AvailableActions::where('input', $model)
            ->get();

        return $actions;
    }

    public static function doAction($objectId, $action, ...$params)
    {
        $object = BackupAgents::where('uuid', $objectId)->first();

        $action = AvailableActions::where('name', $action)
            ->where('input', 'NextDeveloper\S3\BackupAgents')
            ->first();

        $class = $action->class;

        if(class_exists($class)) {
            $action = new $class($object, $params);
            $actionId = $action->getActionId();

            if(request()->get('fg') == 'true') {
                $action->handle();
                return $actionId;
            }

            dispatch($action);

            return $actionId;
        }

        return null;
    }

    public static function getById($id) : ?BackupAgents
    {
        return BackupAgents::where('id', $id)->first();
    }

    public static function relatedObjects($uuid, $object)
    {
        try {
            $obj = BackupAgents::where('uuid', $uuid)->first();

            if(!$obj) {
                throw new ModelNotFoundException('Cannot find the related model');
            }

            if($obj) {
                return $obj->$object;
            }
        } catch (\Exception $e) {
            dd($e);
        }
    }

    /**
     * This method created the model from an array.
     *
     * @param  array $data
     * @return mixed
     * @throw  Exception
     */
    public static function create(array $data)
    {
        if (array_key_exists('s3_bucket_id', $data)) {
            $data['s3_bucket_id'] = DatabaseHelper::uuidToId(
                '\NextDeveloper\S3\Database\Models\Buckets',
                $data['s3_bucket_id']
            );
        }
        if (array_key_exists('iam_account_id', $data)) {
            $data['iam_account_id'] = DatabaseHelper::uuidToId(
                '\NextDeveloper\IAM\Database\Models\Accounts',
                $data['iam_account_id']
            );
        }

        if(!array_key_exists('iam_account_id', $data)) {
            $data['iam_account_id'] = UserHelper::currentAccount()->id;
        }
        if (array_key_exists('iam_user_id', $data)) {
            $data['iam_user_id'] = DatabaseHelper::uuidToId(
                '\NextDeveloper\IAM\Database\Models\Users',
                $data['iam_user_id']
            );
        }

        if(!array_key_exists('iam_user_id', $data)) {
            $data['iam_user_id']    = UserHelper::me()->id;
        }

        try {
            $model = BackupAgents::create($data);
        } catch(\Exception $e) {
            throw $e;
        }

        return $model->fresh();
    }

    public static function updateRaw(array $data) : ?BackupAgents
    {
        if(array_key_exists('id', $data)) {
            return self::update($data['id'], $data);
        }

        return null;
    }

    public static function update($id, array $data)
    {
        $model = BackupAgents::where('uuid', $id)->first();

        if(!$model) {
            throw new NotAllowedException(
                'We cannot find the related object to update. ' .
                'Maybe you dont have the permission to update this object?'
            );
        }

        if (array_key_exists('s3_bucket_id', $data)) {
            $data['s3_bucket_id'] = DatabaseHelper::uuidToId(
                '\NextDeveloper\S3\Database\Models\Buckets',
                $data['s3_bucket_id']
            );
        }
        if (array_key_exists('iam_account_id', $data)) {
            $data['iam_account_id'] = DatabaseHelper::uuidToId(
                '\NextDeveloper\IAM\Database\Models\Accounts',
                $data['iam_account_id']
            );
        }
        if (array_key_exists('iam_user_id', $data)) {
            $data['iam_user_id'] = DatabaseHelper::uuidToId(
                '\NextDeveloper\IAM\Database\Models\Users',
                $data['iam_user_id']
            );
        }

        try {
            $model->update($data);
            $model = $model->fresh();
        } catch(\Exception $e) {
            throw $e;
        }

        return $model->fresh();
    }

    public static function delete($id)
    {
        $model = BackupAgents::where('uuid', $id)->first();

        if(!$model) {
            throw new NotAllowedException(
                'We cannot find the related object to delete. ' .
                'Maybe you dont have the permission to update this object?'
            );
        }

        try {
            $model = $model->delete();
        } catch(\Exception $e) {
            throw $e;
        }

        return $model;
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

}
