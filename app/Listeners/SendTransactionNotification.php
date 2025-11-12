<?php

namespace App\Listeners;

use App\Events\TransactionEffectuee;
use App\Mail\TransactionNotificationMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendTransactionNotification
{
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
    public function handle(TransactionEffectuee $event): void
    {
        $transaction = $event->transaction;
        $client = $event->client;

        // Log de la notification
        Log::info('=== NOTIFICATION TRANSACTION ===');
        Log::info('Client: ' . $client->email);
        Log::info('Type: ' . $transaction->type);
        Log::info('Montant: ' . $transaction->montant . ' FCFA');
        Log::info('Numéro: ' . $transaction->numero_transaction);

        try {
            // Envoi de l'email de notification
            Mail::to($client->email)->send(new TransactionNotificationMail($transaction, $client));

            Log::info('Email de notification envoyé avec succès à: ' . $client->email);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email de notification: ' . $e->getMessage());
            Log::error('Classe d\'erreur: ' . get_class($e));
            Log::error('Code d\'erreur: ' . $e->getCode());

            // Tentative avec Mail::raw() comme fallback
            try {
                Log::info('Tentative d\'envoi avec Mail::raw()');

                $messageContent = "Notification de transaction\n\n";
                $messageContent .= "Cher(e) {$client->prenom} {$client->nom},\n\n";
                $messageContent .= "Une transaction a été effectuée sur votre compte.\n\n";
                $messageContent .= "Détails de la transaction :\n";
                $messageContent .= "- Type : " . ucfirst($transaction->type) . "\n";
                $messageContent .= "- Montant : " . number_format($transaction->montant, 2, ',', ' ') . " FCFA\n";
                $messageContent .= "- Numéro de transaction : " . $transaction->numero_transaction . "\n";
                $messageContent .= "- Date : " . $transaction->date_transaction->format('d/m/Y H:i:s') . "\n";
                $messageContent .= "- Statut : " . ucfirst($transaction->statut) . "\n\n";

                if ($transaction->description) {
                    $messageContent .= "- Description : " . $transaction->description . "\n\n";
                }

                $messageContent .= "Cordialement,\nBanque API";

                Mail::raw($messageContent, function ($message) use ($client, $transaction) {
                    $message->to($client->email)
                            ->subject('Notification de transaction - ' . ucfirst($transaction->type) . ' - Banque API');
                });

                Log::info('Email de notification de fallback envoyé avec succès');

            } catch (\Exception $fallbackError) {
                Log::error('Échec même du fallback: ' . $fallbackError->getMessage());
            }
        }

        // Ici, vous pourriez ajouter d'autres types de notifications :
        // - SMS via un service comme Twilio
        // - Notification push mobile
        // - Notification système dans l'application
        // - Webhook vers un service externe
    }
}