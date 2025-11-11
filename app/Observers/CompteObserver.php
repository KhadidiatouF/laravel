<?php

namespace App\Observers;

use App\Models\Compte;
Use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Http\Services\SmsService;
use App\Mail\AuthentificationEmail;

class CompteObserver
{
    /**
     * Handle the Compte "created" event.
     */
    public function created(Compte $compte): void
    {
        // Charger les relations nécessaires
        $compte->load(['client.user']);

        if ($compte->client && $compte->client->user) {
            $user = $compte->client->user;
            Log::info("Un nouvel utilisateur a été créé : " . $user->nom);

            // Envoyer l'email d'authentification
            $password = $user->plain_password ?? 'password123'; // Utiliser le mot de passe en clair stocké temporairement
            $code = $user->code;

            Mail::to($user->email)->send(new AuthentificationEmail($user, $password, $code));

            // Envoyer le SMS avec le code
            $smsService = app(SmsService::class);
            $message = "Votre code d'authentification est : " . $user->code;
            $smsService->sendSms($user->telephone, $message);
        } else {
            Log::warning("Impossible de charger les relations client.user pour le compte " . $compte->id);
        }
    }

    public function creating(Compte $compte): void
    {
        if (empty($compte->id)) {
            $compte->id = (string) Str::uuid();
        }

        if (empty($compte->numCompte)) {
            do {
                $numero = self::generateAccountNumber();
            } while (Compte::where('numCompte', $numero)->exists());

            $compte->numCompte = $numero;
        }
    }

    /**
     * Handle the Compte "updated" event.
     */
    public function updated(Compte $compte): void
    {
        //
    }

    /**
     * Handle the Compte "deleted" event.
     */
    public function deleted(Compte $compte): void
    {
        //
    }

    /**
     * Handle the Compte "restored" event.
     */
    public function restored(Compte $compte): void
    {
        //
    }

    /**
     * Handle the Compte "force deleted" event.
     */
    public function forceDeleted(Compte $compte): void
    {
        //
    }

    private static function generateAccountNumber(): string
    {
        $prefix = 'JAMILABANK-';
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(4));

        return $prefix . $date . '-' . $random;
    }
}
