<?php

namespace NextDeveloper\S3\Console\Commands;

use Illuminate\Console\Command;
use NextDeveloper\S3\Jobs\ParseBandwidthJob;

class ParseBandwidthCommand extends Command
{
    protected $signature = 's3:parse-bandwidth';
    protected $description = 'Sync current egress/ingress usage into the monthly bandwidth tracking table';

    public function handle(): void
    {
        $this->info('Dispatching bandwidth parse job...');
        ParseBandwidthJob::dispatch();
        $this->info('Done.');
    }
}
