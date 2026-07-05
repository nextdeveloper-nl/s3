<?php

namespace NextDeveloper\S3\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use NextDeveloper\IAM\Helpers\UserHelper;
use NextDeveloper\S3\Database\Models\Accounts;
use NextDeveloper\S3\Services\BandwidthMonthliesService;

/**
 * Run hourly via scheduler.
 *
 * Syncs each account's current egress_bytes_mo_used into the
 * bandwidth_monthlies table for reporting and trend analysis.
 */
class ParseBandwidthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function handle(): void
    {
        $yearMonth = now()->format('Y-m');

        // withoutGlobalScopes() + runAsAdmin(): this runs from the scheduler with no
        // authenticated user, so the AuthorizationScope on Accounts would otherwise
        // match nothing, and model observer writes would be denied by UserHelper::can().
        UserHelper::runAsAdmin(function () use ($yearMonth) {
            Accounts::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->each(function (Accounts $account) use ($yearMonth) {
                    if (($account->egress_bytes_mo_used ?? 0) > 0) {
                        // Sync total from the account's denormalised counter
                        // by upserting the current cumulative value for the month
                        $record = \NextDeveloper\S3\Database\Models\BandwidthMonthlies::where('s3_account_id', $account->id)
                            ->whereYear('month', now()->year)
                            ->whereMonth('month', now()->month)
                            ->first();

                        if ($record) {
                            $record->update([
                                'egress_bytes' => $account->egress_bytes_mo_used,
                                'ingress_bytes' => $record->ingress_bytes,
                            ]);
                        } else {
                            BandwidthMonthliesService::addEgress($account->id, $account->egress_bytes_mo_used, $yearMonth);
                        }
                    }
                });
        });
    }
}
