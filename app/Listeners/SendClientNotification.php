<?php

namespace App\Listeners;

use App\Events\CompteCreated;
use App\Mail\AuthentificationEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

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
        try {
            Mail::to($client->email)->send(new AuthentificationEmail($client, $password, $code));

            Log::info('Email d\'authentification envoyé avec succès', [
                'to' => $client->email,
                'client_id' => $client->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email d\'authentification', [
                'to' => $client->email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendSMSCode($client, $code)
    {
        try {
            $twilioSid = config('services.twilio.sid');
            $twilioToken = config('services.twilio.token');
            $twilioFrom = config('services.twilio.from');

            if (!$twilioSid || !$twilioToken || !$twilioFrom) {
                Log::warning('Configuration Twilio manquante, SMS non envoyé', [
                    'to' => $client->telephone,
                ]);
                return;
            }

            $twilio = new Client($twilioSid, $twilioToken);

            // Pour les comptes d'essai, utiliser le numéro de test
            $fromNumber = $twilioFrom;

            // Liste des numéros de test Twilio (gratuits)
            $testNumbers = ['+15005550006', '+15005550001', '+15005550002', '+17623374603'];

            if (in_array($fromNumber, $testNumbers)) {
                // Pour les numéros de test, on ne peut envoyer qu'aux numéros vérifiés
                // Simulation d'envoi réussi pour les tests
                Log::info('SMS simulé avec numéro de test Twilio', [
                    'from' => $fromNumber,
                    'to' => $client->telephone,
                    'body' => "Banque Example - Votre code de vérification : {$code}. Utilisez-le pour activer votre compte.",
                    'note' => 'Ceci est une simulation car vous utilisez un numéro de test Twilio'
                ]);

                // Ici vous pouvez ajouter une logique pour envoyer un SMS réel
                // via un autre service ou pour les tests
                return;
            }

            $message = $twilio->messages->create(
                $client->telephone,
                [
                    'from' => $fromNumber,
                    'body' => "Banque Example - Votre code de vérification : {$code}. Utilisez-le pour activer votre compte."
                ]
            );

            Log::info('SMS envoyé avec succès via Twilio', [
                'to' => $client->telephone,
                'from' => $fromNumber,
                'message_sid' => $message->sid,
                'status' => $message->status,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi du SMS via Twilio', [
                'to' => $client->telephone,
                'from' => $twilioFrom,
                'error' => $e->getMessage(),
            ]);

            // Fallback: Log détaillé pour debug
            Log::warning('Détails de configuration Twilio', [
                'sid_configured' => !empty($twilioSid),
                'token_configured' => !empty($twilioToken),
                'from_configured' => !empty($twilioFrom),
                'from_number' => $twilioFrom,
            ]);
        }
    }
}