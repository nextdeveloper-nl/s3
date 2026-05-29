<?php

namespace NextDeveloper\S3\Actions\Accounts;

use NextDeveloper\Commons\Actions\AbstractAction;
use NextDeveloper\Commons\Exceptions\NotAllowedException;
use NextDeveloper\S3\Database\Models\Accounts;
use NextDeveloper\S3\Services\AccountsService;

/**
 * Blocks an S3 account, preventing all S3 operations.
 *
 * A reason must be provided via the $params array: ['reason' => '...']
 */
class Block extends AbstractAction
{
    public const EVENTS = [
        'blocking:NextDeveloper\S3\Accounts',
        'blocked:NextDeveloper\S3\Accounts',
    ];

    public function __construct(Accounts $account, $params = null)
    {
        $this->model  = $account;
        $this->queue  = 's3';

        parent::__construct();
    }

    public function handle(): void
    {
        $this->setProgress(0, 'Blocking S3 account');

        if ($this->model->status === 'blocked') {
            $this->setFinished('Account is already blocked, nothing to do.');
            return;
        }

        $reason = is_array($this->params) && !empty($this->params['reason'])
            ? $this->params['reason']
            : 'Manually blocked via action';

        AccountsService::block($this->model->uuid, $reason);

        $this->setProgress(100, 'Account blocked: ' . $reason);
    }
}
