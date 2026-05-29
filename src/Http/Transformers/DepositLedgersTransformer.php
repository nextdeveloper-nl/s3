<?php

namespace NextDeveloper\S3\Http\Transformers;

use Illuminate\Support\Facades\Cache;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\S3\Database\Models\DepositLedgers;
use NextDeveloper\Commons\Http\Transformers\AbstractTransformer;
use NextDeveloper\S3\Http\Transformers\AbstractTransformers\AbstractDepositLedgersTransformer;

/**
 * Class DepositLedgersTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\S3\Http\Transformers
 */
class DepositLedgersTransformer extends AbstractDepositLedgersTransformer
{

    /**
     * @param DepositLedgers $model
     *
     * @return array
     */
    public function transform(DepositLedgers $model)
    {
        $transformed = Cache::get(
            CacheHelper::getKey('DepositLedgers', $model->uuid, 'Transformed')
        );

        if($transformed) {
            return $transformed;
        }

        $transformed = parent::transform($model);

        Cache::set(
            CacheHelper::getKey('DepositLedgers', $model->uuid, 'Transformed'),
            $transformed
        );

        return $transformed;
    }
}
