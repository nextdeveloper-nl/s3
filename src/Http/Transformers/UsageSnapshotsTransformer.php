<?php

namespace NextDeveloper\S3\Http\Transformers;

use Illuminate\Support\Facades\Cache;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\S3\Database\Models\UsageSnapshots;
use NextDeveloper\Commons\Http\Transformers\AbstractTransformer;
use NextDeveloper\S3\Http\Transformers\AbstractTransformers\AbstractUsageSnapshotsTransformer;

/**
 * Class UsageSnapshotsTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\S3\Http\Transformers
 */
class UsageSnapshotsTransformer extends AbstractUsageSnapshotsTransformer
{

    /**
     * @param UsageSnapshots $model
     *
     * @return array
     */
    public function transform(UsageSnapshots $model)
    {
        $transformed = Cache::get(
            CacheHelper::getKey('UsageSnapshots', $model->uuid, 'Transformed')
        );

        if($transformed) {
            return $transformed;
        }

        $transformed = parent::transform($model);

        Cache::set(
            CacheHelper::getKey('UsageSnapshots', $model->uuid, 'Transformed'),
            $transformed
        );

        return $transformed;
    }
}
