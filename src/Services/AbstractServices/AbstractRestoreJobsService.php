<?php

namespace NextDeveloper\S3\Services\AbstractServices;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use NextDeveloper\Commons\Helpers\DatabaseHelper;
use NextDeveloper\IAM\Helpers\UserHelper;
use NextDeveloper\S3\Database\Models\RestoreJobs;
use NextDeveloper\S3\Database\Filters\RestoreJobsQueryFilter;
use NextDeveloper\Commons\Exceptions\ModelNotFoundException;
use NextDeveloper\Commons\Exceptions\NotAllowedException;

/**
 * This class is responsible from managing the data for RestoreJobs
 *
 * Class AbstractRestoreJobsService.
 *
 * @package NextDeveloper\S3\Database\Models
 */
class AbstractRestoreJobsService
{
    public static function get(?RestoreJobsQueryFilter $filter = null, array $params = []) : Collection|LengthAwarePaginator
    {
        $enablePaginate = array_key_exists('paginate', $params);

        $request = new Request();

        if($filter == null) {
            $filter = new RestoreJobsQueryFilter($request);
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

        $model = RestoreJobs::filter($filter);

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
        return RestoreJobs::all();
    }

    public static function getByRef($ref) : ?RestoreJobs
    {
        return RestoreJobs::findByRef($ref);
    }

    public static function getById($id) : ?RestoreJobs
    {
        return RestoreJobs::where('id', $id)->first();
    }

    public static function relatedObjects($uuid, $object)
    {
        try {
            $obj = RestoreJobs::where('uuid', $uuid)->first();

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
     * Restore jobs are only ever created internally
     * (BackupAgentCommandService::restoreSnapshot()) — there is no public
     * store() endpoint on RestoreJobsController.
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

        if (array_key_exists('s3_backup_job_run_id', $data) && $data['s3_backup_job_run_id']) {
            $data['s3_backup_job_run_id'] = DatabaseHelper::uuidToId(
                '\NextDeveloper\S3\Database\Models\BackupJobRuns',
                $data['s3_backup_job_run_id']
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
            $data['iam_user_id'] = UserHelper::me()->id;
        }

        try {
            $model = RestoreJobs::create($data);
        } catch(\Exception $e) {
            throw $e;
        }

        return $model->fresh();
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

}
