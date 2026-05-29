<?php

namespace NextDeveloper\S3\Http\Transformers;

use Illuminate\Support\Facades\Cache;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\S3\Database\Models\AccessKeysPerspective;
use NextDeveloper\Commons\Http\Transformers\AbstractTransformer;
use NextDeveloper\S3\Http\Transformers\AbstractTransformers\AbstractAccessKeysPerspectiveTransformer;

/**
 * Class AccessKeysPerspectiveTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\S3\Http\Transformers
 */
class AccessKeysPerspectiveTransformer extends AbstractAccessKeysPerspectiveTransformer
{

    /**
     * @param AccessKeysPerspective $model
     *
     * @return array
     */
    public function transform(AccessKeysPerspective $model)
    {
        $transformed = Cache::get(
            CacheHelper::getKey('AccessKeysPerspective', $model->uuid, 'Transformed')
        );

        if($transformed) {
            return $transformed;
        }

        $transformed = parent::transform($model);

        Cache::set(
            CacheHelper::getKey('AccessKeysPerspective', $model->uuid, 'Transformed'),
            $transformed
        );

        return $transformed;
    }
}
