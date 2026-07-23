<?php

namespace NextDeveloper\S3\Actions\Servers;

use NextDeveloper\Commons\Actions\AbstractAction;
use NextDeveloper\S3\Database\Models\Servers;
use NextDeveloper\S3\Services\ServersService;

/**
 * Ensures this server has its Marketplace Product, PAYG catalog, and the
 * configured default paid tiers (see ServersService::ensurePackaging()).
 *
 * Idempotent — safe to trigger any time: to fix a server that predates
 * packaging or was seeded outside ServersService::create(), or to roll a
 * newly added config('s3.packaging.default_tiers') entry out to a server
 * that already exists, without waiting for the next
 * `s3:ensure-server-packaging` backfill run.
 */
class EnsurePackaging extends AbstractAction
{
    public const EVENTS = [
        'ensuring-packaging:NextDeveloper\S3\Servers',
        'ensured-packaging:NextDeveloper\S3\Servers',
    ];

    public function __construct(Servers $server, $params = null)
    {
        $this->model = $server;
        $this->queue = 's3';

        parent::__construct();
    }

    public function handle(): void
    {
        $this->setProgress(0, 'Ensuring server packaging (Marketplace product + catalogs)');

        ServersService::ensurePackaging($this->model);

        $this->setProgress(100, 'Server packaging ensured');
    }
}
