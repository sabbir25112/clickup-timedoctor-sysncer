<?php

namespace App\Console;

use App\Console\Commands\ClickUpFetchAllTasks;
use App\Console\Commands\DeleteClickUpTimeLogs;
use App\Console\Commands\FetchClickUpTimeLogs;
use App\Console\Commands\TimeDoctorFetchAllTasks;
use App\Console\Commands\TimeDoctorWorkLogFetcher;
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
        TimeDoctorWorkLogFetcher::class,
        ClickUpFetchAllTasks::class,
        FetchClickUpTimeLogs::class,
        DeleteClickUpTimeLogs::class,
        TimeDoctorFetchAllTasks::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('run:SyncEr')
            ->timezone('UTC')
            ->dailyAt('01:00');
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
