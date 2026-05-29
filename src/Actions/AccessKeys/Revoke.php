<?php

namespace NextDeveloper\S3\Actions\AccessKeys;

use NextDeveloper\Commons\Actions\AbstractAction;
use NextDeveloper\S3\Database\Models\AccessKeys;
use NextDeveloper\S3\Services\AccessKeysService;

/**
 * Revokes an S3 access key so it can no longer authenticate.
 *
 * Optional param: ['reason' => '...']
 */
class Revoke extends AbstractAction
{
    public const EVENTS = [
        'revoking:NextDeveloper\S3\AccessKeys',
        'revoked:NextDeveloper\S3\AccessKeys',
    ];

    public function __construct(AccessKeys $accessKey, $params = null)
    {
        $this->model  = $accessKey;
        $this->queue  = 's3';

        parent::__construct();
    }

    public function handle(): void
    {
        $this->setProgress(0, 'Revoking access key');

        if ($this->model->status === 'revoked') {
            $this->setFinished('Access key is already revoked, nothing to do.');
            return;
        }

        $reason = is_array($this->params) && !empty($this->params['reason'])
            ? $this->params['reason']
            : 'Manually revoked via action';

        AccessKeysService::revoke($this->model->uuid, $reason);

        $this->setProgress(100, 'Access key revoked: ' . $reason);
    }
}
