<?php

namespace NextDeveloper\S3\Actions\Buckets;

use NextDeveloper\Commons\Actions\AbstractAction;
use NextDeveloper\S3\Database\Models\Buckets;
use NextDeveloper\S3\Services\BucketsService;

/**
 * Enables versioning on an S3 bucket.
 *
 * Once enabled, versioning can only be suspended, not fully disabled.
 */
class EnableVersioning extends AbstractAction
{
    public const EVENTS = [
        'versioning-enabling:NextDeveloper\S3\Buckets',
        'versioning-enabled:NextDeveloper\S3\Buckets',
    ];

    public function __construct(Buckets $bucket, $params = null)
    {
        $this->model = $bucket;
        $this->queue = 's3';

        parent::__construct();
    }

    public function handle(): void
    {
        $this->setProgress(0, 'Enabling bucket versioning');

        if ($this->model->versioning === 'Enabled') {
            $this->setFinished('Versioning is already enabled on this bucket.');
            return;
        }

        if ($this->model->object_lock_enabled) {
            // Object Lock implicitly requires versioning — it is already enabled internally
            $this->setFinished('This bucket has Object Lock enabled; versioning is already active.');
            return;
        }

        BucketsService::update($this->model->uuid, ['versioning' => 'Enabled']);

        $this->setProgress(100, 'Bucket versioning enabled');
    }
}
