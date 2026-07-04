<?php

namespace NextDeveloper\S3\Http\Transformers;

use Illuminate\Support\Facades\Cache;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\S3\Database\Models\BackupJobs;
use NextDeveloper\S3\Http\Transformers\AbstractTransformers\AbstractBackupJobsTransformer;

/**
 * Class BackupJobsTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\S3\Http\Transformers
 */
class BackupJobsTransformer extends AbstractBackupJobsTransformer
{

    /**
     * @param BackupJobs $model
     *
     * @return array
     */
    public function transform(BackupJobs $model)
    {
        $transformed = Cache::get(
            CacheHelper::getKey('BackupJobs', $model->uuid, 'Transformed')
        );

        if(!$transformed) {
            $transformed = parent::transform($model);

            Cache::set(
                CacheHelper::getKey('BackupJobs', $model->uuid, 'Transformed'),
                $transformed
            );
        }

        return $transformed;
    }
}
