<?php

namespace NextDeveloper\S3\Http\Transformers;

use Illuminate\Support\Facades\Cache;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\S3\Database\Models\Buckets;
use NextDeveloper\Commons\Http\Transformers\AbstractTransformer;
use NextDeveloper\S3\Http\Transformers\AbstractTransformers\AbstractBucketsTransformer;

/**
 * Class BucketsTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\S3\Http\Transformers
 */
class BucketsTransformer extends AbstractBucketsTransformer
{

    /**
     * @param Buckets $model
     *
     * @return array
     */
    public function transform(Buckets $model)
    {
        $transformed = Cache::get(
            CacheHelper::getKey('Buckets', $model->uuid, 'Transformed')
        );

        if($transformed) {
            return $transformed;
        }

        $transformed = parent::transform($model);

        Cache::set(
            CacheHelper::getKey('Buckets', $model->uuid, 'Transformed'),
            $transformed
        );

        return $transformed;
    }
}
