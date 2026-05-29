<?php

namespace NextDeveloper\S3\Http\Transformers;

use Illuminate\Support\Facades\Cache;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\S3\Database\Models\ServersPerspective;
use NextDeveloper\Commons\Http\Transformers\AbstractTransformer;
use NextDeveloper\S3\Http\Transformers\AbstractTransformers\AbstractServersPerspectiveTransformer;

/**
 * Class ServersPerspectiveTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\S3\Http\Transformers
 */
class ServersPerspectiveTransformer extends AbstractServersPerspectiveTransformer
{

    /**
     * @param ServersPerspective $model
     *
     * @return array
     */
    public function transform(ServersPerspective $model)
    {
        $transformed = Cache::get(
            CacheHelper::getKey('ServersPerspective', $model->uuid, 'Transformed')
        );

        if($transformed) {
            return $transformed;
        }

        $transformed = parent::transform($model);

        Cache::set(
            CacheHelper::getKey('ServersPerspective', $model->uuid, 'Transformed'),
            $transformed
        );

        return $transformed;
    }
}
