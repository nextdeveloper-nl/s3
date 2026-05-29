<?php

namespace NextDeveloper\S3\Http\Transformers;

use Illuminate\Support\Facades\Cache;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\S3\Database\Models\NotificationsSents;
use NextDeveloper\Commons\Http\Transformers\AbstractTransformer;
use NextDeveloper\S3\Http\Transformers\AbstractTransformers\AbstractNotificationsSentsTransformer;

/**
 * Class NotificationsSentsTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\S3\Http\Transformers
 */
class NotificationsSentsTransformer extends AbstractNotificationsSentsTransformer
{

    /**
     * @param NotificationsSents $model
     *
     * @return array
     */
    public function transform(NotificationsSents $model)
    {
        $transformed = Cache::get(
            CacheHelper::getKey('NotificationsSents', $model->uuid, 'Transformed')
        );

        if($transformed) {
            return $transformed;
        }

        $transformed = parent::transform($model);

        Cache::set(
            CacheHelper::getKey('NotificationsSents', $model->uuid, 'Transformed'),
            $transformed
        );

        return $transformed;
    }
}
