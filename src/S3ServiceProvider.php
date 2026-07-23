<?php

namespace NextDeveloper\S3;

use Illuminate\Console\Scheduling\Schedule;
use NextDeveloper\Commons\AbstractServiceProvider;

/**
 * Class CommunicationServiceProvider
 *
 * @package NextDeveloper\Communication
 */
class S3ServiceProvider extends AbstractServiceProvider
{
    /**
     * @var bool
     */
    protected $defer = false;

    /**
     * @throws \Exception
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes(
            [
            __DIR__.'/../config/s3.php' => config_path('s3.php'),
            ], 'config'
        );

        $this->loadViewsFrom($this->dir.'/../resources/views', 'S3');
    }

    /**
     * @return void
     */
    public function register()
    {
        $this->registerHelpers();
        $this->registerRoutes();
        $this->registerCommands();

        $this->mergeConfigFrom(__DIR__.'/../config/s3.php', 's3');
    }

    /**
     * @return array
     */
    public function provides()
    {
        return ['events'];
    }

    /**
     * Register module routes
     *
     * @return void
     */
    protected function registerRoutes()
    {
        if ( ! $this->app->routesAreCached() && config('leo.allowed_routes.s3', true) ) {
            $this->app['router']
                ->namespace('NextDeveloper\S3\Http\Controllers')
                ->group(__DIR__.DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'api.routes.php');
        }
    }

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * Registers module based commands — override to include S3 commands.
     */
    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \NextDeveloper\S3\Console\Commands\CheckQuotasCommand::class,
                \NextDeveloper\S3\Console\Commands\ParseBandwidthCommand::class,
                \NextDeveloper\S3\Console\Commands\CleanupMultipartCommand::class,
                \NextDeveloper\S3\Console\Commands\WormLifecycleCommand::class,
                \NextDeveloper\S3\Console\Commands\CheckMissedBackupsCommand::class,
                \NextDeveloper\S3\Console\Commands\EnsureServerPackagingCommand::class,
            ]);
        }
    }

    /**
     * Register the cron schedule for background S3 jobs.
     *
     * Call this from the host application's AppServiceProvider::boot() via
     *   $this->app->make(S3ServiceProvider::class)->schedule();
     * or let it be picked up automatically if the host uses the AbstractServiceProvider.
     */
    public function bootSchedule()
    {
        $this->app->booted(function () {
            /** @var Schedule $schedule */
            $schedule = $this->app->make(Schedule::class);

            // Quota enforcement — every 15 minutes
            $schedule->job(new \NextDeveloper\S3\Jobs\CheckQuotasJob())
                ->everyFifteenMinutes()
                ->name('s3:check-quotas')
                ->withoutOverlapping();

            // Bandwidth sync — every hour
            $schedule->job(new \NextDeveloper\S3\Jobs\ParseBandwidthJob())
                ->hourly()
                ->name('s3:parse-bandwidth')
                ->withoutOverlapping();

            // Monthly bandwidth reset — 1st of month at 00:05
            $schedule->job(new \NextDeveloper\S3\Jobs\ResetBandwidthJob())
                ->monthlyOn(1, '00:05')
                ->name('s3:reset-bandwidth');

            // Stale multipart upload cleanup — daily at 03:00
            $schedule->job(new \NextDeveloper\S3\Jobs\CleanupMultipartUploadsJob())
                ->dailyAt('03:00')
                ->name('s3:cleanup-multipart')
                ->withoutOverlapping();

            // WORM commitment lifecycle — daily at 02:00
            $schedule->job(new \NextDeveloper\S3\Jobs\WormLifecycleJob())
                ->dailyAt('02:00')
                ->name('s3:worm-lifecycle')
                ->withoutOverlapping();

            // Missed backup-job detection — every 15 minutes
            $schedule->job(new \NextDeveloper\S3\Jobs\CheckMissedBackupsJob())
                ->everyFifteenMinutes()
                ->name('s3:backup-agents-check-missed')
                ->withoutOverlapping();
        });
    }
}
