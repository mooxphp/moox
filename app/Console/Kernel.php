<?php

namespace App\Console;

use Override;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    #[Override]
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('moox:batchjob')->daily();
        $schedule->command('moox:demojob')->hourly();
        // $schedule->command('moox:failjob')->cron('0 */3 * * *');        // Every 3 minutes
        // $schedule->command('moox:longjob')->cron('0 */45 * * *');       // Every 45 minutes
        // $schedule->command('moox:timeoutjob')->cron('0 */20 * * *');    // Every 20 minutes
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    #[Override]
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
