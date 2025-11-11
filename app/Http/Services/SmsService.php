<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Log;

class SmsService
{
    public function sendSms($telephone, $message)
    {
        // Simulation de l'envoi SMS (remplacer par un vrai service comme Twilio)
        Log::info("SMS envoyé à {$telephone}: {$message}");

        // Ici, intégrer un vrai service SMS
        // Exemple avec Twilio :
        // $twilio = new Client(env('TWILIO_SID'), env('TWILIO_TOKEN'));
        // $twilio->messages->create($telephone, [
        //     'from' => env('TWILIO_FROM'),
        //     'body' => $message
        // ]);

        return true;
    }
}