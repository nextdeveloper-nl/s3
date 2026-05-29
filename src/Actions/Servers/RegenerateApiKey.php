<?php

namespace NextDeveloper\S3\Actions\Servers;

use NextDeveloper\Commons\Actions\AbstractAction;
use NextDeveloper\S3\Database\Models\Servers;
use NextDeveloper\S3\Services\ServersService;

/**
 * Rotates the storaged agent API key for a storage server.
 *
 * Use when a key is suspected to be compromised or as part of a
 * routine security rotation. The agent must be reconfigured with
 * the new key after this action completes.
 */
class RegenerateApiKey extends AbstractAction
{
    public const EVENTS = [
        'api-key-regenerating:NextDeveloper\S3\Servers',
        'api-key-regenerated:NextDeveloper\S3\Servers',
    ];

    public function __construct(Servers $server, $params = null)
    {
        $this->model = $server;
        $this->queue = 's3';

        parent::__construct();
    }

    public function handle(): void
    {
        $this->setProgress(0, 'Regenerating storaged agent API key');

        $updated = ServersService::regenerateApiKey($this->model->uuid);

        $this->setProgress(100, 'API key regenerated. Agent must be reconfigured before it can reconnect.');
    }
}
