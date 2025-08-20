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
        // Auto reconcile SePay transactions daily at 6 AM
        $schedule->command('payments:reconcile --auto-sepay')
            ->dailyAt('06:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Detect suspicious patterns daily at 7 AM
        $schedule->command('payments:reconcile --detect-suspicious')
            ->dailyAt('07:00')
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
