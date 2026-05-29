<?php

namespace NextDeveloper\S3\Http\Transformers;

use Illuminate\Support\Facades\Cache;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\S3\Database\Models\BandwidthMonthlies;
use NextDeveloper\Commons\Http\Transformers\AbstractTransformer;
use NextDeveloper\S3\Http\Transformers\AbstractTransformers\AbstractBandwidthMonthliesTransformer;

/**
 * Class BandwidthMonthliesTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\S3\Http\Transformers
 */
class BandwidthMonthliesTransformer extends AbstractBandwidthMonthliesTransformer
{

    /**
     * @param BandwidthMonthlies $model
     *
     * @return array
     */
    public function transform(BandwidthMonthlies $model)
    {
        $transformed = Cache::get(
            CacheHelper::getKey('BandwidthMonthlies', $model->uuid, 'Transformed')
        );

        if($transformed) {
            return $transformed;
        }

        $transformed = parent::transform($model);

        Cache::set(
            CacheHelper::getKey('BandwidthMonthlies', $model->uuid, 'Transformed'),
            $transformed
        );

        return $transformed;
    }
}
