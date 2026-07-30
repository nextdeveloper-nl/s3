<?php

namespace NextDeveloper\S3\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use NextDeveloper\IAM\Helpers\UserHelper;
use NextDeveloper\S3\Database\Models\Accounts;
use NextDeveloper\S3\Database\Models\Buckets;
use NextDeveloper\S3\Database\Models\Servers;
use NextDeveloper\S3\Services\AccountsService;

/**
 * On-demand backfill, dispatched by EnsureAccountPaygSubscriptionCommand.
 *
 * BucketsService::create() now hard-blocks new buckets unless the account
 * ends up with an active subscription (see
 * AccountsService::ensurePaygSubscriptionOrFail()), but that only covers
 * buckets created after that change shipped. This job walks every (account,
 * server) pair that already has at least one bucket and runs the same
 * check via the soft/logging variant (ensurePaygSubscription() — one
 * unsellable server must not abort the whole backfill), so accounts that
 * were consuming storage/egress with no billing anchor before this existed
 * get subscribed to Pay-As-You-Go too. No-op for any pair that already has
 * an active subscription (PAYG or a paid tier).
 */
class EnsureAccountPaygSubscriptionJob implements ShouldQueue
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
        // no authenticated user, so AuthorizationScope on Accounts/Servers
        // would otherwise match nothing, and the Subscriptions writes inside
        // ensurePaygSubscription() would be denied by UserHelper::can().
        UserHelper::runAsAdmin(function () {
            Buckets::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->select('s3_account_id', 's3_server_id')
                ->distinct()
                ->get()
                ->each(function (Buckets $pair) {
                    $account = Accounts::withoutGlobalScopes()->find($pair->s3_account_id);
                    $server  = Servers::withoutGlobalScopes()->find($pair->s3_server_id);

                    if ($account && $server) {
                        AccountsService::ensurePaygSubscription($account, $server);
                    }
                });
        });
    }
}
