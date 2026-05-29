<?php

namespace NextDeveloper\S3\Http\Transformers;

use Illuminate\Support\Facades\Cache;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\S3\Database\Models\Webhooks;
use NextDeveloper\Commons\Http\Transformers\AbstractTransformer;
use NextDeveloper\S3\Http\Transformers\AbstractTransformers\AbstractWebhooksTransformer;

/**
 * Class WebhooksTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\S3\Http\Transformers
 */
class WebhooksTransformer extends AbstractWebhooksTransformer
{

    /**
     * @param Webhooks $model
     *
     * @return array
     */
    public function transform(Webhooks $model)
    {
        $transformed = Cache::get(
            CacheHelper::getKey('Webhooks', $model->uuid, 'Transformed')
        );

        if($transformed) {
            return $transformed;
        }

        $transformed = parent::transform($model);

        Cache::set(
            CacheHelper::getKey('Webhooks', $model->uuid, 'Transformed'),
            $transformed
        );

        return $transformed;
    }
}
