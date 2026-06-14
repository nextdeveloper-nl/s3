<?php

namespace NextDeveloper\S3\Http\Transformers;

use Illuminate\Support\Facades\Cache;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\S3\Database\Models\Servers;
use NextDeveloper\S3\Http\Transformers\AbstractTransformers\AbstractServersTransformer;

/**
 * Class ServersTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\S3\Http\Transformers
 */
class ServersTransformer extends AbstractServersTransformer
{

    /**
     * @param Servers $model
     *
     * @return array
     */
    public function transform(Servers $model)
    {
        $transformed = Cache::get(
            CacheHelper::getKey('Servers', $model->uuid, 'Transformed')
        );

        if(!$transformed) {
            $transformed = parent::transform($model);

            Cache::set(
                CacheHelper::getKey('Servers', $model->uuid, 'Transformed'),
                $transformed
            );
        }

        // Pricing fields are appended after the cache lookup so they always
        // reflect the live model value and are never stale from a cached entry.
        $transformed['price_per_gb']       = $model->price_per_gb;
        $transformed['common_currency_id'] = $model->common_currency_id;

        return $transformed;
    }
}
