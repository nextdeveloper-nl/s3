<?php

namespace NextDeveloper\S3\Http\Transformers;

use Illuminate\Support\Facades\Cache;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\S3\Database\Models\AccessKeys;
use NextDeveloper\Commons\Http\Transformers\AbstractTransformer;
use NextDeveloper\S3\Http\Transformers\AbstractTransformers\AbstractAccessKeysTransformer;

/**
 * Class AccessKeysTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\S3\Http\Transformers
 */
class AccessKeysTransformer extends AbstractAccessKeysTransformer
{

    /**
     * @param AccessKeys $model
     *
     * @return array
     */
    public function transform(AccessKeys $model)
    {
        // AccessKeysService::create() attaches the plaintext secret as a transient,
        // non-persisted attribute so it can be shown to the customer exactly once,
        // right after creation. Read it before touching the cache below, since the
        // cached payload must never contain it.
        $plainSecret = $model->plain_secret ?? null;

        $transformed = Cache::get(
            CacheHelper::getKey('AccessKeys', $model->uuid, 'Transformed')
        );

        if (!$transformed) {
            $transformed = parent::transform($model);

            // The encrypted blob is an internal implementation detail — customers
            // should never receive it, only the plaintext secret (below) at
            // creation time.
            unset($transformed['secret_key_enc']);

            Cache::set(
                CacheHelper::getKey('AccessKeys', $model->uuid, 'Transformed'),
                $transformed
            );
        }

        if ($plainSecret !== null) {
            $transformed['secret_key_enc'] = $plainSecret;
        }

        return $transformed;
    }
}
