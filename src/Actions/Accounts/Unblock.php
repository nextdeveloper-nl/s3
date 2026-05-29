<?php

namespace NextDeveloper\S3\Actions\Accounts;

use NextDeveloper\Commons\Actions\AbstractAction;
use NextDeveloper\S3\Database\Models\Accounts;
use NextDeveloper\S3\Services\AccountsService;

/**
 * Unblocks an S3 account, restoring normal S3 operations.
 *
 * Should only be used after the underlying quota/policy issue has been resolved.
 */
class Unblock extends AbstractAction
{
    public const EVENTS = [
        'unblocking:NextDeveloper\S3\Accounts',
        'unblocked:NextDeveloper\S3\Accounts',
    ];

    public function __construct(Accounts $account, $params = null)
    {
        $this->model = $account;
        $this->queue = 's3';

        parent::__construct();
    }

    public function handle(): void
    {
        $this->setProgress(0, 'Unblocking S3 account');

        if ($this->model->status !== 'blocked') {
            $this->setFinished('Account is not blocked, nothing to do.');
            return;
        }

        AccountsService::unblock($this->model->uuid);

        $this->setProgress(100, 'Account unblocked and restored to active');
    }
}
