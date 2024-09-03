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
        $schedule->command('app:deleting-orders')->everyMinute();
        // $schedule->command('app:started-discount')->everyMinute();
        // $schedule->command('app:deleting-orders')->everyThirtyMinutes();
        // $schedule->command('app:truncate-check-tables')->daily();
        // $schedule->command('app:started-discount')->daily();
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
