<?php

namespace NextDeveloper\S3\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use NextDeveloper\S3\Services\WormCommitmentsService;

/**
 * Run daily at 02:00 via scheduler.
 *
 * Transitions WORM commitments through their lifecycle:
 *   active → expired (when locks_until has passed)
 *   expired → purged (after the configured grace period)
 */
class WormLifecycleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function handle(): void
    {
        WormCommitmentsService::processExpired();
        WormCommitmentsService::processPurge();
    }
}
