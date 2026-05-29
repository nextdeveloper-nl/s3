<?php

namespace NextDeveloper\S3\Console\Commands;

use Illuminate\Console\Command;
use NextDeveloper\S3\Jobs\WormLifecycleJob;

class WormLifecycleCommand extends Command
{
    protected $signature = 's3:worm-lifecycle';
    protected $description = 'Process WORM commitment lifecycle: expire active commitments and purge old ones';

    public function handle(): void
    {
        $this->info('Dispatching WORM lifecycle job...');
        WormLifecycleJob::dispatch();
        $this->info('Done.');
    }
}
