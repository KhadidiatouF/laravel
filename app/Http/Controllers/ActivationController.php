<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Traits\ApiResponseTrait;
use App\Http\Services\OtpService;
use App\Http\Services\SmsService;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Activation",
 *     description="Endpoints d'activation de comptes clients"
 * )
 */
class ActivationController extends Controller
{
    use ApiResponseTrait;

    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Vérifier et activer un compte avec OTP
     *
     * @OA\Post(
     *     path="/api/verify-otp",
     *     summary="Vérifier le code OTP et activer le compte",
     *     tags={"Activation"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone", "otp_code", "otp_token"},
     *             @OA\Property(property="telephone", type="string", example="+221771234567"),
     *             @OA\Property(property="otp_code", type="string", example="123456"),
     *             @OA\Property(property="otp_token", type="string", example="random_token_here")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte activé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte activé avec succès")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Données invalides"),
     *     @OA\Response(response=404, description="Token OTP invalide"),
     *     @OA\Response(response=410, description="Code OTP expiré"),
     *     @OA\Response(response=401, description="Code OTP incorrect")
     * )
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'telephone' => 'required|string',
            'otp_code' => 'required|string|size:6',
        ]);

        // Trouver le client par numéro de téléphone
        $client = Client::where('telephone', $request->telephone)->first();

        if (!$client) {
            return $this->errorResponse('Numéro de téléphone non trouvé.', 404);
        }

        // Vérifier si l'OTP n'est pas expiré
        if ($this->otpService->isExpired($client->otp_expires_at)) {
            return $this->errorResponse('Code OTP expiré. Veuillez demander un nouveau code.', 410);
        }

        // Vérifier le code OTP
        if ($client->code_verification !== $request->otp_code) {
            return $this->errorResponse('Code OTP incorrect.', 401);
        }

        // Activer le compte : supprimer les données temporaires
        $client->update([
            'code_verification' => null,
        ]);

        return $this->successResponse(
            message: 'Compte activé avec succès. Vous pouvez maintenant vous connecter.'
        );
    }

    /**
     * Renvoyer un nouveau code OTP
     *
     * @OA\Post(
     *     path="/api/resend-otp",
     *     summary="Renvoyer un code OTP",
     *     tags={"Activation"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone"},
     *             @OA\Property(property="telephone", type="string", example="+221771234567")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Nouveau code OTP envoyé",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Nouveau code OTP envoyé")
     *         )
     *     )
     * )
     */
    public function resendOtp(Request $request, SmsService $smsService)
    {
        $request->validate([
            'telephone' => 'required|string',
        ]);

        $client = Client::where('telephone', $request->telephone)->first();

        if (!$client) {
            return $this->errorResponse('Numéro de téléphone non trouvé.', 404);
        }

        // Générer nouveau OTP
        $otpCode = $this->otpService->generateOtp();
        $otpToken = $this->otpService->generateOtpToken();
        $otpExpiresAt = $this->otpService->getExpirationDate(2);

        // Mettre à jour le client
        $client->update([
            'code_verification' => $otpCode,
            'otp_token' => $otpToken,
            'otp_expires_at' => $otpExpiresAt,
        ]);

        // Envoyer par SMS
        $smsService->sendOtpSms($client->telephone, $otpCode, 'activation');

        return $this->successResponse(
            message: 'Nouveau code OTP envoyé par SMS.'
        );
    }
}