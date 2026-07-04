<?php

namespace NextDeveloper\S3\Http\Transformers;

use Illuminate\Support\Facades\Cache;
use NextDeveloper\Commons\Common\Cache\CacheHelper;
use NextDeveloper\S3\Database\Models\BackupAgents;
use NextDeveloper\S3\Http\Transformers\AbstractTransformers\AbstractBackupAgentsTransformer;

/**
 * Class BackupAgentsTransformer. This class is being used to manipulate the data we are serving to the customer
 *
 * @package NextDeveloper\S3\Http\Transformers
 */
class BackupAgentsTransformer extends AbstractBackupAgentsTransformer
{

    /**
     * @param BackupAgents $model
     *
     * @return array
     */
    public function transform(BackupAgents $model)
    {
        $transformed = Cache::get(
            CacheHelper::getKey('BackupAgents', $model->uuid, 'Transformed')
        );

        if(!$transformed) {
            $transformed = parent::transform($model);

            Cache::set(
                CacheHelper::getKey('BackupAgents', $model->uuid, 'Transformed'),
                $transformed
            );
        }

        return $transformed;
    }
}
