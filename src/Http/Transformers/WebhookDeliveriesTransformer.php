<?php

namespace NextDeveloper\S3\Http\Transformers;

use Illuminate\Support\Facades\Cache;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\S3\Database\Models\WebhookDeliveries;
use NextDeveloper\Commons\Http\Transformers\AbstractTransformer;
use NextDeveloper\S3\Http\Transformers\AbstractTransformers\AbstractWebhookDeliveriesTransformer;

/**
 * Class WebhookDeliveriesTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\S3\Http\Transformers
 */
class WebhookDeliveriesTransformer extends AbstractWebhookDeliveriesTransformer
{

    /**
     * @param WebhookDeliveries $model
     *
     * @return array
     */
    public function transform(WebhookDeliveries $model)
    {
        $transformed = Cache::get(
            CacheHelper::getKey('WebhookDeliveries', $model->uuid, 'Transformed')
        );

        if($transformed) {
            return $transformed;
        }

        $transformed = parent::transform($model);

        Cache::set(
            CacheHelper::getKey('WebhookDeliveries', $model->uuid, 'Transformed'),
            $transformed
        );

        return $transformed;
    }
}
