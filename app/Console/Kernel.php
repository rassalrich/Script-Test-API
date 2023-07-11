<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\SymlinkCommand::class,
        \App\Console\Commands\SubscribeMqttCommand::class,
        \App\Console\Commands\ExecuteAutomaticJobsCommand::class,
        \App\Console\Commands\ExecuteSceneJobsCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('execute:scene-jobs')->everyMinute();
        $schedule->command('execute:automatic-jobs')->everyMinute();
    }
}
