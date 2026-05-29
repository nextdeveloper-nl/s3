<?php

namespace NextDeveloper\S3\Http\Transformers;

use Illuminate\Support\Facades\Cache;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\S3\Database\Models\ServerTelemetries;
use NextDeveloper\Commons\Http\Transformers\AbstractTransformer;
use NextDeveloper\S3\Http\Transformers\AbstractTransformers\AbstractServerTelemetriesTransformer;

/**
 * Class ServerTelemetriesTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\S3\Http\Transformers
 */
class ServerTelemetriesTransformer extends AbstractServerTelemetriesTransformer
{

    /**
     * @param ServerTelemetries $model
     *
     * @return array
     */
    public function transform(ServerTelemetries $model)
    {
        $transformed = Cache::get(
            CacheHelper::getKey('ServerTelemetries', $model->uuid, 'Transformed')
        );

        if($transformed) {
            return $transformed;
        }

        $transformed = parent::transform($model);

        Cache::set(
            CacheHelper::getKey('ServerTelemetries', $model->uuid, 'Transformed'),
            $transformed
        );

        return $transformed;
    }
}
