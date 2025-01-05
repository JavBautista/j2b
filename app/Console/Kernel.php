<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    /*protected $commands=[
        Commands\TestTask::class
    ];*/

    protected function scheduleTimezone(){
        return 'America/Mexico_City';
    }

    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        //$schedule->command('test:task')->everyMinute();
        //everyTwoMinutes()
        //$schedule->command('payment_reminders:generate')->everyMinute();
        $schedule->command('payment_reminders:generate')->daily();
        $schedule->command('create:rents_notifications')->daily();
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
}
