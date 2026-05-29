<?php

namespace NextDeveloper\S3\Http\Transformers;

use Illuminate\Support\Facades\Cache;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\S3\Database\Models\AccountStats;
use NextDeveloper\Commons\Http\Transformers\AbstractTransformer;
use NextDeveloper\S3\Http\Transformers\AbstractTransformers\AbstractAccountStatsTransformer;

/**
 * Class AccountStatsTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\S3\Http\Transformers
 */
class AccountStatsTransformer extends AbstractAccountStatsTransformer
{

    /**
     * @param AccountStats $model
     *
     * @return array
     */
    public function transform(AccountStats $model)
    {
        $transformed = Cache::get(
            CacheHelper::getKey('AccountStats', $model->uuid, 'Transformed')
        );

        if($transformed) {
            return $transformed;
        }

        $transformed = parent::transform($model);

        Cache::set(
            CacheHelper::getKey('AccountStats', $model->uuid, 'Transformed'),
            $transformed
        );

        return $transformed;
    }
}
