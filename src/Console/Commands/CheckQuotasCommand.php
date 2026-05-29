<?php

namespace NextDeveloper\S3\Console\Commands;

use Illuminate\Console\Command;
use NextDeveloper\S3\Jobs\CheckQuotasJob;

class CheckQuotasCommand extends Command
{
    protected $signature = 's3:check-quotas';
    protected $description = 'Take usage snapshots and enforce quota thresholds for all active S3 accounts';

    public function handle(): void
    {
        $this->info('Dispatching quota check for all active accounts...');
        CheckQuotasJob::dispatch();
        $this->info('Done.');
    }
}
