<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Sync WSO2 admin users every 6 hours
        $schedule
            ->job(new \App\Jobs\SyncWSO2AdminUsersJob())
            ->cron('0 */6 * * *')  // Every 6 hours at minute 0
            ->description('Sync WSO2 admin users')
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('WSO2 admin users sync scheduled job completed successfully');
            })
            ->onFailure(function () {
                \Log::error('WSO2 admin users sync scheduled job failed');
            });

        // Sync WSO2 applications every 12 hours (less frequent)
        $schedule
            ->job(new \App\Jobs\SyncWSO2ApplicationsJob())
            ->cron('0 */12 * * *')  // Every 12 hours at minute 0
            ->description('Sync WSO2 applications')
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('WSO2 applications sync scheduled job completed successfully');
            })
            ->onFailure(function () {
                \Log::error('WSO2 applications sync scheduled job failed');
            });

        // Alternative: Run commands directly (less preferred for long operations)
        // $schedule->command('wso2:sync-admin-users')
        //          ->cron('0 */6 * * *')
        //          ->description('Sync WSO2 admin users')
        //          ->withoutOverlapping();
        //
        // $schedule->command('wso2:sync-applications')
        //          ->cron('0 */12 * * *')
        //          ->description('Sync WSO2 applications')
        //          ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
