<?php

namespace NextDeveloper\S3\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
        Accounts::whereIn('status', ['active', 'warning'])
            ->whereNull('deleted_at')
            ->each(function (Accounts $account) {
                UsageSnapshotsService::checkAndEnforce($account);
            });
    }
}
