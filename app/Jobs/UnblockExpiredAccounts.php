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
        // Trouver tous les comptes bloqués dont la date de fin de blocage est dépassée
        $expiredBlockedAccounts = Compte::where('statut', 'bloqué')
            ->where('date_fin_bloquage', '<', now())
            ->get();

        foreach ($expiredBlockedAccounts as $compte) {
            // Débloquer le compte (remettre à actif)
            $compte->update([
                'statut' => 'actif',
                'date_debut_bloquage' => null,
                'date_fin_bloquage' => null,
            ]);

            Log::info('Compte débloqué automatiquement après expiration du blocage', [
                'compte_id' => $compte->id,
                'numero_compte' => $compte->numCompte,
                'date_fin_bloquage' => $compte->date_fin_bloquage,
            ]);
        }

        Log::info('Job de déblocage des comptes expirés terminé', [
            'comptes_debloques' => $expiredBlockedAccounts->count(),
        ]);
    }
}