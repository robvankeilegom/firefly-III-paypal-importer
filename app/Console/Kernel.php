<?php

namespace App\Console;

use App\Console\Commands\Sync;
use App\Console\Commands\SyncPayPal;
use App\Console\Commands\SyncFirefly;
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
        Sync::class,
        SyncPayPal::class,
        SyncFirefly::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command(Sync::class)->daily();
    }
}
