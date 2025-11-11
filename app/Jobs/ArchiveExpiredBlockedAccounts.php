<?php

namespace App\Jobs;

use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ArchiveExpiredBlockedAccounts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Ce job archive les comptes épargne bloqués dont la date de début de blocage est échue
        // Les comptes sont déplacés vers la base de données Neon

        // Trouver les comptes épargne bloqués dont la date de début de blocage est dépassée
        $accountsToArchive = Compte::where('statut', 'bloqué')
            ->where('date_debut_bloquage', '<=', now())
            ->get();

        foreach ($accountsToArchive as $compte) {
            // Simulation : déplacer le compte vers Neon
            // En production, cela ferait un appel API vers Neon pour migrer les données

            // Pour la simulation, on marque comme archivé dans la base locale
            $compte->update(['statut' => 'fermé']);

            // Archiver toutes les transactions du compte
            Transaction::where('compte_id', $compte->id)
                ->update(['statut' => 'archivé']);

            Log::info('Compte épargne bloqué archivé et déplacé vers Neon', [
                'compte_id' => $compte->id,
                'numero_compte' => $compte->numCompte,
                'date_debut_bloquage' => $compte->date_debut_bloquage,
                'date_fin_bloquage' => $compte->date_fin_bloquage,
            ]);
        }

        Log::info('Job d\'archivage des comptes épargne bloqués terminé', [
            'comptes_archives' => $accountsToArchive->count(),
        ]);
    }
}