<?php

namespace NextDeveloper\S3\Console\Commands;

use Illuminate\Console\Command;
use NextDeveloper\S3\Jobs\EnsureAccountPaygSubscriptionJob;

class EnsureAccountPaygSubscriptionCommand extends Command
{
    protected $signature = 's3:ensure-payg-subscriptions';
    protected $description = 'Backfill: subscribe every (account, server) pair that already has a bucket but no active subscription to that server\'s Pay-As-You-Go catalog entry';

    public function handle(): void
    {
        $this->info('Dispatching PAYG subscription backfill for all S3 accounts...');
        EnsureAccountPaygSubscriptionJob::dispatch();
        $this->info('Done.');
    }
}
