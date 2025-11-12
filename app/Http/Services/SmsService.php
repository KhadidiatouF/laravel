<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Log;
use Exception;

class SmsService
{
    /**
     * Envoyer un SMS
     */
    public function sendSms(string $telephone, string $message): bool
    {
        try {
            // En développement, logger seulement
            if (app()->environment(['local', 'development', 'testing'])) {
                Log::info("SMS DEV envoyé à {$telephone}: {$message}");
                return true;
            }

            // En production, envoi réel (Twilio par défaut)
            return $this->sendRealSms($telephone, $message);

        } catch (Exception $e) {
            Log::error("Erreur envoi SMS à {$telephone}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoyer un SMS réel via Twilio
     */
    private function sendRealSms(string $telephone, string $message): bool
    {
        try {
            $twilio = new \Twilio\Rest\Client(
                config('services.twilio.sid'),
                config('services.twilio.token')
            );

            $result = $twilio->messages->create(
                $this->formatPhoneNumber($telephone),
                [
                    'from' => config('services.twilio.from'),
                    'body' => $message
                ]
            );

            Log::info("SMS Twilio envoyé - SID: {$result->sid}");
            return true;

        } catch (Exception $e) {
            Log::error("Erreur Twilio SMS: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoyer un code OTP par SMS
     */
    public function sendOtpSms(string $telephone, string $otpCode, string $context = 'activation'): bool
    {
        $message = $this->buildOtpMessage($otpCode, $context);
        return $this->sendSms($telephone, $message);
    }

    /**
     * Formater le numéro de téléphone
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Supprimer tous les caractères non numériques
        $phone = preg_replace('/\D/', '', $phone);

        // Ajouter le préfixe international si nécessaire
        if (!str_starts_with($phone, '+')) {
            // Pour le Sénégal, ajouter +221
            if (str_starts_with($phone, '221')) {
                $phone = '+' . $phone;
            } elseif (str_starts_with($phone, '77') || str_starts_with($phone, '78') || str_starts_with($phone, '76')) {
                $phone = '+221' . $phone;
            }
        }

        return $phone;
    }

    /**
     * Construire le message SMS pour l'OTP
     */
    private function buildOtpMessage(string $otpCode, string $context): string
    {
        switch ($context) {
            case 'activation':
                return "BANQUE API - Votre code d'activation: {$otpCode}. Valide 2 minutes.";
            case 'login':
                return "BANQUE API - Code de connexion: {$otpCode}";
            case 'reset':
                return "BANQUE API - Code de réinitialisation: {$otpCode}";
            default:
                return "BANQUE API - Votre code: {$otpCode}";
        }
    }
}