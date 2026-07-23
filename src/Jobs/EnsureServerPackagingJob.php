<?php

namespace NextDeveloper\S3\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use NextDeveloper\IAM\Helpers\UserHelper;
use NextDeveloper\S3\Database\Models\Servers;
use NextDeveloper\S3\Services\ServersService;

/**
 * On-demand backfill, dispatched by EnsureServerPackagingCommand.
 *
 * Runs ServersService::ensurePackaging() for every S3 server, so servers
 * created before packaging existed — or created through any path that
 * bypassed ServersService::create() (e.g. a data import/seed) — end up
 * with a Marketplace Product, PAYG catalog, and the configured default
 * paid tiers. ensurePackaging() is idempotent, so this is also the way to
 * roll a newly added default tier (config('s3.packaging.default_tiers'))
 * out to every existing server.
 */
class EnsureServerPackagingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const QUEUE_NAME = 's3';

    public int $timeout = 300;

    public function __construct()
    {
        $this->onQueue(self::QUEUE_NAME);
    }

    public function handle(): void
    {
        // withoutGlobalScopes() + runAsAdmin(): this runs from the console with
        // no authenticated user, so the AuthorizationScope on Servers would
        // otherwise match nothing, and the Products/ProductCatalogs writes
        // inside ensurePackaging() would be denied by UserHelper::can().
        UserHelper::runAsAdmin(function () {
            Servers::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->each(function (Servers $server) {
                    ServersService::ensurePackaging($server);
                });
        });
    }
}
