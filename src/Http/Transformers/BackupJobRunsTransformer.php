<?php

namespace NextDeveloper\S3\Http\Transformers;

use Illuminate\Support\Facades\Cache;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\S3\Database\Models\BackupJobRuns;
use NextDeveloper\S3\Http\Transformers\AbstractTransformers\AbstractBackupJobRunsTransformer;

/**
 * Class BackupJobRunsTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\S3\Http\Transformers
 */
class BackupJobRunsTransformer extends AbstractBackupJobRunsTransformer
{

    /**
     * @param BackupJobRuns $model
     *
     * @return array
     */
    public function transform(BackupJobRuns $model)
    {
        $transformed = Cache::get(
            CacheHelper::getKey('BackupJobRuns', $model->uuid, 'Transformed')
        );

        if(!$transformed) {
            $transformed = parent::transform($model);

            Cache::set(
                CacheHelper::getKey('BackupJobRuns', $model->uuid, 'Transformed'),
                $transformed
            );
        }

        return $transformed;
    }
}
