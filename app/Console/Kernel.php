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
        // Archiver les comptes bloqués expirés toutes les heures
        $schedule->job(new ArchiveExpiredBlockedAccounts)->hourly();

        // Débloquer les comptes dont le blocage est terminé toutes les heures
        $schedule->job(new UnblockExpiredAccounts)->hourly();
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
