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
        // Trouver tous les comptes bloqués dont la date de fin de blocage est dépassée
        $expiredBlockedAccounts = Compte::where('statut', 'bloqué')
            ->where('date_fin_bloquage', '<', now())
            ->get();

        foreach ($expiredBlockedAccounts as $compte) {
            // Archiver le compte
            $compte->update(['statut' => 'fermé']);

            // Archiver toutes les transactions du compte
            Transaction::where('compte_id', $compte->id)
                ->update(['statut' => 'archivé']);

            Log::info('Compte archivé automatiquement après expiration du blocage', [
                'compte_id' => $compte->id,
                'numero_compte' => $compte->numCompte,
                'date_fin_bloquage' => $compte->date_fin_bloquage,
            ]);
        }

        Log::info('Job d\'archivage des comptes bloqués expirés terminé', [
            'comptes_archives' => $expiredBlockedAccounts->count(),
        ]);
    }
}