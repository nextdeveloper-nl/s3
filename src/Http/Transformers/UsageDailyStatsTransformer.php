<?php

namespace NextDeveloper\S3\Http\Transformers;

use Illuminate\Support\Facades\Cache;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\S3\Database\Models\UsageDailyStats;
use NextDeveloper\Commons\Http\Transformers\AbstractTransformer;
use NextDeveloper\S3\Http\Transformers\AbstractTransformers\AbstractUsageDailyStatsTransformer;

/**
 * Class UsageDailyStatsTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\S3\Http\Transformers
 */
class UsageDailyStatsTransformer extends AbstractUsageDailyStatsTransformer
{

    /**
     * @param UsageDailyStats $model
     *
     * @return array
     */
    public function transform(UsageDailyStats $model)
    {
        $transformed = Cache::get(
            CacheHelper::getKey('UsageDailyStats', $model->uuid, 'Transformed')
        );

        if($transformed) {
            return $transformed;
        }

        $transformed = parent::transform($model);

        Cache::set(
            CacheHelper::getKey('UsageDailyStats', $model->uuid, 'Transformed'),
            $transformed
        );

        return $transformed;
    }
}
