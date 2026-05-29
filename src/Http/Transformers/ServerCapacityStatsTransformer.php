<?php

namespace NextDeveloper\S3\Http\Transformers;

use Illuminate\Support\Facades\Cache;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\S3\Database\Models\ServerCapacityStats;
use NextDeveloper\Commons\Http\Transformers\AbstractTransformer;
use NextDeveloper\S3\Http\Transformers\AbstractTransformers\AbstractServerCapacityStatsTransformer;

/**
 * Class ServerCapacityStatsTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\S3\Http\Transformers
 */
class ServerCapacityStatsTransformer extends AbstractServerCapacityStatsTransformer
{

    /**
     * @param ServerCapacityStats $model
     *
     * @return array
     */
    public function transform(ServerCapacityStats $model)
    {
        $transformed = Cache::get(
            CacheHelper::getKey('ServerCapacityStats', $model->uuid, 'Transformed')
        );

        if($transformed) {
            return $transformed;
        }

        $transformed = parent::transform($model);

        Cache::set(
            CacheHelper::getKey('ServerCapacityStats', $model->uuid, 'Transformed'),
            $transformed
        );

        return $transformed;
    }
}
