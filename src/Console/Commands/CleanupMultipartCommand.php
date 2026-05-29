<?php

namespace NextDeveloper\S3\Console\Commands;

use Illuminate\Console\Command;
use NextDeveloper\S3\Jobs\CleanupMultipartUploadsJob;

class CleanupMultipartCommand extends Command
{
    protected $signature = 's3:cleanup-multipart';
    protected $description = 'Abort and remove multipart uploads inactive for more than 48 hours';

    public function handle(): void
    {
        $this->info('Dispatching multipart upload cleanup...');
        CleanupMultipartUploadsJob::dispatch();
        $this->info('Done.');
    }
}
