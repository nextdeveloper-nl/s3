<?php

namespace NextDeveloper\S3\Http\Transformers;

use Illuminate\Support\Facades\Cache;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\S3\Database\Models\AccessKeys;
use NextDeveloper\Commons\Http\Transformers\AbstractTransformer;
use NextDeveloper\S3\Http\Transformers\AbstractTransformers\AbstractAccessKeysTransformer;

/**
 * Class AccessKeysTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\S3\Http\Transformers
 */
class AccessKeysTransformer extends AbstractAccessKeysTransformer
{

    /**
     * @param AccessKeys $model
     *
     * @return array
     */
    public function transform(AccessKeys $model)
    {
        $transformed = Cache::get(
            CacheHelper::getKey('AccessKeys', $model->uuid, 'Transformed')
        );

        if($transformed) {
            return $transformed;
        }

        $transformed = parent::transform($model);

        Cache::set(
            CacheHelper::getKey('AccessKeys', $model->uuid, 'Transformed'),
            $transformed
        );

        return $transformed;
    }
}
