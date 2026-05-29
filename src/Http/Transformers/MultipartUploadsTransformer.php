<?php

namespace NextDeveloper\S3\Http\Transformers;

use Illuminate\Support\Facades\Cache;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\S3\Database\Models\MultipartUploads;
use NextDeveloper\Commons\Http\Transformers\AbstractTransformer;
use NextDeveloper\S3\Http\Transformers\AbstractTransformers\AbstractMultipartUploadsTransformer;

/**
 * Class MultipartUploadsTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\S3\Http\Transformers
 */
class MultipartUploadsTransformer extends AbstractMultipartUploadsTransformer
{

    /**
     * @param MultipartUploads $model
     *
     * @return array
     */
    public function transform(MultipartUploads $model)
    {
        $transformed = Cache::get(
            CacheHelper::getKey('MultipartUploads', $model->uuid, 'Transformed')
        );

        if($transformed) {
            return $transformed;
        }

        $transformed = parent::transform($model);

        Cache::set(
            CacheHelper::getKey('MultipartUploads', $model->uuid, 'Transformed'),
            $transformed
        );

        return $transformed;
    }
}
