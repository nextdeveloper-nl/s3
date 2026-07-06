<?php

namespace NextDeveloper\S3\Http\Transformers;

use Illuminate\Support\Facades\Cache;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\S3\Database\Models\RestoreJobs;
use NextDeveloper\S3\Http\Transformers\AbstractTransformers\AbstractRestoreJobsTransformer;

/**
 * Class RestoreJobsTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\S3\Http\Transformers
 */
class RestoreJobsTransformer extends AbstractRestoreJobsTransformer
{

    /**
     * @param RestoreJobs $model
     *
     * @return array
     */
    public function transform(RestoreJobs $model)
    {
        $transformed = Cache::get(
            CacheHelper::getKey('RestoreJobs', $model->uuid, 'Transformed')
        );

        if(!$transformed) {
            $transformed = parent::transform($model);

            Cache::set(
                CacheHelper::getKey('RestoreJobs', $model->uuid, 'Transformed'),
                $transformed
            );
        }

        return $transformed;
    }
}
