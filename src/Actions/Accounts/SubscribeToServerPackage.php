<?php

namespace NextDeveloper\S3\Actions\Accounts;

use NextDeveloper\Commons\Actions\AbstractAction;
use NextDeveloper\S3\Database\Models\Accounts;
use NextDeveloper\S3\Services\AccountsService;

/**
 * Subscribes an S3 account to a package on a specific server.
 * Required params: ['server_id' => '<uuid>', 'product_catalog_id' => '<public template catalog uuid>']
 */
class SubscribeToServerPackage extends AbstractAction
{
    public const EVENTS = [
        'subscribing-server-package:NextDeveloper\S3\Accounts',
        'subscribed-server-package:NextDeveloper\S3\Accounts',
    ];

    // Declaring PARAMS is what makes AbstractAction unwrap/validate $params
    // (Block/Unblock/Revoke never declare it, which is why their own
    // optional "reason" params silently never populate — those siblings
    // also call parent::__construct() with no argument; this action's
    // params are required, so it must not copy that pattern).
    public const PARAMS = [
        'server_id'          => 'required|uuid',
        'product_catalog_id' => 'required|uuid',
    ];

    public function __construct(Accounts $account, $params = null)
    {
        $this->model = $account;
        $this->queue = 's3';

        parent::__construct($params);
    }

    public function handle(): void
    {
        $this->setProgress(0, 'Subscribing to S3 package');

        AccountsService::subscribeToServerPackage(
            $this->model->uuid,
            $this->params['server_id'],
            $this->params['product_catalog_id']
        );

        $this->setProgress(100, 'Subscribed');
    }
}
