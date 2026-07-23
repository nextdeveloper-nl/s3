<?php

namespace NextDeveloper\S3\Console\Commands;

use Illuminate\Console\Command;
use NextDeveloper\S3\Jobs\EnsureServerPackagingJob;

class EnsureServerPackagingCommand extends Command
{
    protected $signature = 's3:ensure-server-packaging';
    protected $description = 'Backfill: ensure every S3 server has a Marketplace Product, PAYG catalog, and the configured default paid tiers';

    public function handle(): void
    {
        $this->info('Dispatching packaging backfill for all S3 servers...');
        EnsureServerPackagingJob::dispatch();
        $this->info('Done.');
    }
}
