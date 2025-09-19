<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\VerifyAssignmentCiphers::class,
        \App\Console\Commands\RecryptAssignments::class,
    ];
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('groups:run-due-draws')
            ->dailyAt('00:05')
            ->withoutOverlapping();

        // Cipher verification (assignments) - toggle via config('encryption.verify_schedule_enabled')
        if (config('encryption.verify_schedule_enabled', true)) {
            $schedule->command('assignments:verify-ciphers')
                ->dailyAt('02:30')
                ->onOneServer()
                ->withoutOverlapping()
                ->runInBackground();
        }
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
