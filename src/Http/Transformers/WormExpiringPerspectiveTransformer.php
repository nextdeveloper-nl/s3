<?php

namespace NextDeveloper\S3\Http\Transformers;

use Illuminate\Support\Facades\Cache;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\S3\Database\Models\WormExpiringPerspective;
use NextDeveloper\Commons\Http\Transformers\AbstractTransformer;
use NextDeveloper\S3\Http\Transformers\AbstractTransformers\AbstractWormExpiringPerspectiveTransformer;

/**
 * Class WormExpiringPerspectiveTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\S3\Http\Transformers
 */
class WormExpiringPerspectiveTransformer extends AbstractWormExpiringPerspectiveTransformer
{

    /**
     * @param WormExpiringPerspective $model
     *
     * @return array
     */
    public function transform(WormExpiringPerspective $model)
    {
        $transformed = Cache::get(
            CacheHelper::getKey('WormExpiringPerspective', $model->uuid, 'Transformed')
        );

        if($transformed) {
            return $transformed;
        }

        $transformed = parent::transform($model);

        Cache::set(
            CacheHelper::getKey('WormExpiringPerspective', $model->uuid, 'Transformed'),
            $transformed
        );

        return $transformed;
    }
}
