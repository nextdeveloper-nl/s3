<?php

namespace NextDeveloper\S3\Services\AbstractServices;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use NextDeveloper\Commons\Helpers\DatabaseHelper;
use NextDeveloper\Commons\Database\Models\AvailableActions;
use NextDeveloper\S3\Database\Models\BackupJobRuns;
use NextDeveloper\S3\Database\Filters\BackupJobRunsQueryFilter;
use NextDeveloper\Commons\Exceptions\ModelNotFoundException;
use NextDeveloper\Commons\Exceptions\NotAllowedException;

/**
 * This class is responsible from managing the data for BackupJobRuns
 *
 * Class AbstractBackupJobRunsService.
 *
 * @package NextDeveloper\S3\Database\Models
 */
class AbstractBackupJobRunsService
{
    public static function get(?BackupJobRunsQueryFilter $filter = null, array $params = []) : Collection|LengthAwarePaginator
    {
        $enablePaginate = array_key_exists('paginate', $params);

        $request = new Request();

        if($filter == null) {
            $filter = new BackupJobRunsQueryFilter($request);
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

        $model = BackupJobRuns::filter($filter);

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
        return BackupJobRuns::all();
    }

    public static function getByRef($ref) : ?BackupJobRuns
    {
        return BackupJobRuns::findByRef($ref);
    }

    public static function getById($id) : ?BackupJobRuns
    {
        return BackupJobRuns::where('id', $id)->first();
    }

    public static function relatedObjects($uuid, $object)
    {
        try {
            $obj = BackupJobRuns::where('uuid', $uuid)->first();

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
     * Runs are only ever created internally (BackupAgentCommandService when a
     * run_job_now/schedule fires, or the missed-backup cron) — there is no
     * public store() endpoint on BackupJobRunsController.
     *
     * @param  array $data
     * @return mixed
     * @throw  Exception
     */
    public static function create(array $data)
    {
        if (array_key_exists('s3_backup_job_id', $data)) {
            $data['s3_backup_job_id'] = DatabaseHelper::uuidToId(
                '\NextDeveloper\S3\Database\Models\BackupJobs',
                $data['s3_backup_job_id']
            );
        }

        try {
            $model = BackupJobRuns::create($data);
        } catch(\Exception $e) {
            throw $e;
        }

        return $model->fresh();
    }

    public static function update($id, array $data)
    {
        $model = BackupJobRuns::where('uuid', $id)->first();

        if(!$model) {
            throw new NotAllowedException(
                'We cannot find the related object to update. ' .
                'Maybe you dont have the permission to update this object?'
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

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

}
