<?php

namespace App\Http\Services;

use Illuminate\Support\Str;
use Carbon\Carbon;

class OtpService
{
    /**
     * Générer un code OTP
     */
    public function generateOtp(int $length = 6): string
    {
        return str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    /**
     * Générer un token unique pour l'OTP
     */
    public function generateOtpToken(): string
    {
        return Str::random(64);
    }

    /**
     * Calculer la date d'expiration (2 minutes par défaut)
     */
    public function getExpirationDate(int $minutes = 2): Carbon
    {
        return now()->addMinutes($minutes);
    }

    /**
     * Vérifier si l'OTP est expiré
     */
    public function isExpired(Carbon $expiresAt): bool
    {
        return now()->isAfter($expiresAt);
    }
}