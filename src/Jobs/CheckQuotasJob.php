<?php

namespace NextDeveloper\S3\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use NextDeveloper\IAM\Helpers\UserHelper;
use NextDeveloper\S3\Database\Models\Accounts;
use NextDeveloper\S3\Services\UsageSnapshotsService;

/**
 * Run every 15 minutes via scheduler.
 *
 * Takes a usage snapshot for every active account and enforces quota
 * thresholds (warning at 80%, block at 100%).
 */
class CheckQuotasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function handle(): void
    {
        // withoutGlobalScopes(): this runs from the scheduler with no authenticated
        // user, so the AuthorizationScope on Accounts would otherwise match nothing.
        // runAsAdmin(): UsageSnapshots/Accounts writes go through model observers
        // that call UserHelper::can(), which denies everything with no logged-in user.
        UserHelper::runAsAdmin(function () {
            Accounts::withoutGlobalScopes()
                ->whereIn('status', ['active', 'warning'])
                ->whereNull('deleted_at')
                ->each(function (Accounts $account) {
                    UsageSnapshotsService::checkAndEnforce($account);
                });
        });
    }
}
