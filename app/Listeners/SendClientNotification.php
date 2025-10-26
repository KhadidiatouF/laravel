<?php

namespace App\Listeners;

use App\Events\CompteCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendClientNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CompteCreated $event): void
    {
        $compte = $event->compte;
        $client = $event->client;

        // Envoyer le mail d'authentification si c'est un nouveau client
        if ($event->password && $event->code) {
            $this->sendAuthenticationEmail($client, $event->password, $event->code);
        }

        // Envoyer le SMS avec le code
        $this->sendSMSCode($client, $event->code ?? 'N/A');

        Log::info('Notifications envoyées pour le compte créé', [
            'compte_id' => $compte->id,
            'client_id' => $client->id,
            'numero_compte' => $compte->numCompte,
        ]);
    }

    private function sendAuthenticationEmail($client, $password, $code)
    {
        // Simulation d'envoi d'email (en production, utiliser un service comme SendGrid, Mailgun, etc.)
        Log::info('Email d\'authentification envoyé', [
            'to' => $client->email,
            'password' => $password,
            'code' => $code,
        ]);

        // Ici vous pouvez intégrer un service d'email réel
        // Mail::to($client->email)->send(new AuthenticationEmail($client, $password, $code));
    }

    private function sendSMSCode($client, $code)
    {
        // Simulation d'envoi de SMS (en production, utiliser un service comme Twilio, Africa's Talking, etc.)
        Log::info('SMS envoyé', [
            'to' => $client->telephone,
            'code' => $code,
        ]);

        // Ici vous pouvez intégrer un service SMS réel
        // $this->smsService->send($client->telephone, "Votre code de vérification: $code");
    }
}