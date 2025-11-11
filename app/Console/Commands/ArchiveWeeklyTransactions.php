<?php

namespace App\Console\Commands;

use App\Jobs\ArchiveWeeklyTransactions;
use Illuminate\Console\Command;

class ArchiveWeeklyTransactionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:archive-weekly
                            {--week= : Numéro de la semaine (par défaut: semaine actuelle)}
                            {--year= : Année (par défaut: année actuelle)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archiver les transactions de la semaine dans MongoDB';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $week = $this->option('week') ?? now()->weekOfYear;
        $year = $this->option('year') ?? now()->year;

        $this->info("🔄 Lancement de l'archivage des transactions...");
        $this->info("Semaine: {$week}, Année: {$year}");

        // Dispatch du job
        ArchiveWeeklyTransactions::dispatch($week, $year);

        $this->info("✅ Job d'archivage envoyé à la queue avec succès!");
        $this->info("Vous pouvez vérifier les logs pour suivre la progression.");

        return Command::SUCCESS;
    }
}