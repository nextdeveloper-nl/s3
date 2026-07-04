<?php

namespace NextDeveloper\S3\Console\Commands;

use Illuminate\Console\Command;
use NextDeveloper\S3\Jobs\CheckMissedBackupsJob;

class CheckMissedBackupsCommand extends Command
{
    protected $signature = 's3:backup-agents-check-missed';
    protected $description = 'Flag backup jobs whose schedule has fired with no completed run since, and notify the account';

    public function handle(): void
    {
        $this->info('Dispatching missed-backup check...');
        CheckMissedBackupsJob::dispatch();
        $this->info('Done.');
    }
}
