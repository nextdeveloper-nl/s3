<?php

namespace NextDeveloper\S3\Actions\Buckets;

use NextDeveloper\Commons\Actions\AbstractAction;
use NextDeveloper\S3\Database\Models\Buckets;
use NextDeveloper\S3\Services\BucketsService;

/**
 * Suspends versioning on an S3 bucket.
 *
 * Cannot be applied to buckets with Object Lock enabled
 * (Object Lock requires versioning to be active).
 */
class SuspendVersioning extends AbstractAction
{
    public const EVENTS = [
        'versioning-suspending:NextDeveloper\S3\Buckets',
        'versioning-suspended:NextDeveloper\S3\Buckets',
    ];

    public function __construct(Buckets $bucket, $params = null)
    {
        $this->model = $bucket;
        $this->queue = 's3';

        parent::__construct();
    }

    public function handle(): void
    {
        $this->setProgress(0, 'Suspending bucket versioning');

        if ($this->model->versioning === 'Suspended' || $this->model->versioning === null) {
            $this->setFinished('Versioning is already suspended on this bucket.');
            return;
        }

        if ($this->model->object_lock_enabled) {
            $this->setFinishedWithError('Cannot suspend versioning: this bucket has Object Lock enabled, which requires versioning to remain active.');
            return;
        }

        BucketsService::update($this->model->uuid, ['versioning' => 'Suspended']);

        $this->setProgress(100, 'Bucket versioning suspended');
    }
}
