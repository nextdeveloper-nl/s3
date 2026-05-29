<?php

namespace NextDeveloper\S3\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use NextDeveloper\S3\Services\MultipartUploadsService;

/**
 * Run daily at 03:00 via scheduler.
 *
 * Aborts and removes multipart uploads that have been inactive for
 * more than 48 hours to reclaim storage.
 */
class CleanupMultipartUploadsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function handle(): void
    {
        MultipartUploadsService::cleanup();
    }
}
