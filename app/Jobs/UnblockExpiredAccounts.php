<?php

namespace App\Jobs;

use App\Models\Compte;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UnblockExpiredAccounts implements ShouldQueue
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
        // Ce job désarchive les comptes épargne bloqués dont la date de fin de blocage est échue
        // Les comptes sont récupérés depuis la base Neon et remis à actif

        // Simulation : vérifier dans la base Neon (cloud) les comptes à désarchiver
        // En production, cela ferait un appel API vers Neon pour récupérer les comptes expirés

        // Pour la simulation, on utilise la logique locale
        // Trouver les comptes épargne bloqués dont la date de fin de blocage est dépassée
        $accountsToUnblock = Compte::where('statut', 'bloqué')
            ->where('type', 'epargne')
            ->where('date_fin_bloquage', '<=', now())
            ->get();

        foreach ($accountsToUnblock as $compte) {
            // Désarchiver le compte (remettre à actif)
            $compte->update([
                'statut' => 'actif',
                'date_debut_bloquage' => null,
                'date_fin_bloquage' => null,
            ]);

            Log::info('Compte épargne désarchivé automatiquement depuis Neon après expiration du blocage', [
                'compte_id' => $compte->id,
                'numero_compte' => $compte->numCompte,
                'type' => $compte->type,
                'date_fin_bloquage' => $compte->date_fin_bloquage,
            ]);
        }

        Log::info('Job de désarchivage des comptes épargne expirés terminé', [
            'comptes_desarchives' => $accountsToUnblock->count(),
        ]);
    }
}