<?php

namespace NextDeveloper\S3\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use NextDeveloper\S3\Services\BandwidthMonthliesService;

/**
 * Run on the 1st of each month at 00:05 via scheduler.
 *
 * Resets monthly egress/ingress counters on both the BandwidthMonthlies
 * records and the denormalised s3_accounts.egress_bytes_mo_used column.
 */
class ResetBandwidthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $previousMonth = now()->subMonth()->format('Y-m');
        BandwidthMonthliesService::resetMonth($previousMonth);

        // Reset the live counter on every account for the new month
        \NextDeveloper\S3\Database\Models\Accounts::whereNull('deleted_at')
            ->update(['egress_bytes_mo_used' => 0]);
    }
}
