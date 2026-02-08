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
        //$schedule->command('payment_reminders:generate')->daily();
        $schedule->command('payment_reminders:generate')->daily();
        $schedule->command('create:rents_notifications')->daily();
        $schedule->command('create:shop_cutoff_notifications')->daily();

        // Recordatorios de renta para clientes con APP (3 días y 1 día antes)
        $schedule->command('create:client_rent_reminders')->dailyAt('10:00');

        // Recordatorios de pago a crédito para clientes con APP (3 días y 1 día antes)
        $schedule->command('create:client_payment_reminders')->dailyAt('10:00');

        // Verificar suscripciones vencidas (trials, grace periods, bloqueos)
        $schedule->command('subscriptions:check-expired')->dailyAt('02:00');
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
