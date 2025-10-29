<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\ArchiveExpiredBlockedAccounts;
use App\Jobs\UnblockExpiredAccounts;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Archiver tous les comptes bloqués chaque minuit
        $schedule->job(new ArchiveExpiredBlockedAccounts)->dailyAt('00:00');

        // Vérifier et débloquer les comptes depuis Neon tous les jours
        $schedule->job(new UnblockExpiredAccounts)->daily();
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
