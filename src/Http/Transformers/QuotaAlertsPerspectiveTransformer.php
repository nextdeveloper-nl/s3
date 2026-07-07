<?php

namespace NextDeveloper\S3\Http\Transformers;

use Illuminate\Support\Facades\Cache;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\S3\Database\Models\QuotaAlertsPerspective;
use NextDeveloper\Commons\Http\Transformers\AbstractTransformer;
use NextDeveloper\S3\Http\Transformers\AbstractTransformers\AbstractQuotaAlertsPerspectiveTransformer;

/**
 * Class QuotaAlertsPerspectiveTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\S3\Http\Transformers
 */
class QuotaAlertsPerspectiveTransformer extends AbstractQuotaAlertsPerspectiveTransformer
{

    /**
     * @param QuotaAlertsPerspective $model
     *
     * @return array
     */
    public function transform(QuotaAlertsPerspective $model)
    {
        $transformed = Cache::get(
            CacheHelper::getKey('QuotaAlertsPerspective', $model->uuid, 'Transformed')
        );

        if($transformed) {
            return $transformed;
        }

        $transformed = parent::transform($model);

        Cache::set(
            CacheHelper::getKey('QuotaAlertsPerspective', $model->uuid, 'Transformed'),
            $transformed
        );

        return $transformed;
    }
}
