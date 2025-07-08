<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\InstallDightsoftSystem::class,
        \App\Console\Commands\SyncShops::class,
        \App\Console\Commands\SyncProducts::class,
        \App\Console\Commands\SyncSales::class,
        \App\Console\Commands\SyncInvoices::class,
        \App\Console\Commands\SyncAllData::class,
        \App\Console\Commands\CheckOverdueInvoices::class,
    ];
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Hourly data sync for all shops
        $schedule->command('sync:all-data --pull')
                 ->hourly()
                 ->appendOutputTo(storage_path('logs/sync.log'));
        
        // Daily overdue invoice checks at 9 AM
        $schedule->command('invoices:check-overdue')
                 ->dailyAt('09:00')
                 ->appendOutputTo(storage_path('logs/invoices.log'));

        // Nightly database backups at 2 AM
        $schedule->command('backup:run --only-db')
                 ->dailyAt('02:00')
                 ->onSuccess(function () {
                     \Log::info('Database backup completed successfully');
                 })
                 ->onFailure(function () {
                     \Log::error('Database backup failed');
                 });

        // Weekly cleanup of old sync logs
        $schedule->command('log:clean --keep-last=7')
                 ->weekly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Get the timezone that should be used by default for scheduled events.
     *
     * @return \DateTimeZone|string|null
     */
    protected function scheduleTimezone()
    {
        return config('app.timezone') ?? 'UTC';
    }
}