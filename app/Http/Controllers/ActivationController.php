<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Traits\ApiResponseTrait;
use App\Http\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

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
     *     path="/api/v1/verify-otp",
     *     summary="Vérifier le code OTP et activer le compte",
     *     tags={"Activation"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone", "otp_code"},
     *             @OA\Property(property="telephone", type="string", example="+221771234567"),
     *             @OA\Property(property="otp_code", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte activé avec succès et connexion automatique",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte activé avec succès. Connexion automatique effectuée."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
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
        if ($client->otp_expires_at && $this->otpService->isExpired(\Carbon\Carbon::parse($client->otp_expires_at))) {
            return $this->errorResponse('Code OTP expiré. Veuillez demander un nouveau code.', 410);
        }

        // Vérifier le code OTP
        if ($client->code_verification !== $request->otp_code) {
            return $this->errorResponse('Code OTP incorrect.', 401);
        }

        // Activer le compte : supprimer les données temporaires et changer le statut à actif
        $client->update([
            'code_verification' => null,
            'otp_expires_at' => null,
            'otp_token' => null,
        ]);

        // Activer tous les comptes du client
        $client->comptes()->update(['statut' => 'actif']);

        // Créer le token d'accès directement
        try {
            $token = $client->createToken('API TOKEN');

            return $this->successResponse([
                'access_token' => $token->accessToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ], 'Compte activé avec succès. Connexion automatique effectuée.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur création token après activation: ' . $e->getMessage());
            return $this->successResponse(
                message: 'Compte activé avec succès. Vous pouvez maintenant vous connecter.'
            );
        }
    }

    /**
     * Renvoyer un nouveau code OTP
     *
     * @OA\Post(
     *     path="/api/v1/resend-otp",
     *     summary="Renvoyer un code OTP",
     *     tags={"Activation"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", example="client@example.com")
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
    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $client = Client::where('email', $request->email)->first();

        if (!$client) {
            return $this->errorResponse('Email non trouvé.', 404);
        }

        // Générer nouveau OTP
        $otpCode = $this->otpService->generateOtp();
        $otpToken = $this->otpService->generateOtpToken();
        $otpExpiresAt = $this->otpService->getExpirationDate(10);

        // Mettre à jour le client
        $client->update([
            'code_verification' => $otpCode,
            'otp_token' => $otpToken,
            'otp_expires_at' => $otpExpiresAt,
        ]);

        // Envoyer par email
        \Illuminate\Support\Facades\Mail::to($client->email)->send(new \App\Mail\OtpEmail($client, $otpCode));

        return $this->successResponse(
            message: 'Nouveau code OTP envoyé par email.'
        );
    }

    /**
     * Modifier le mot de passe PIN
     *
     * @OA\Post(
     *     path="/api/v1/modifier-mdp",
     *     summary="Modifier le mot de passe PIN",
     *     tags={"Activation"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ancien_pin", "nouveau_pin"},
     *             @OA\Property(property="ancien_pin", type="string", example="0000"),
     *             @OA\Property(property="nouveau_pin", type="string", example="1234")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mot de passe modifié avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Mot de passe modifié avec succès")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Données invalides"),
     *     @OA\Response(response=401, description="Ancien PIN incorrect")
     * )
     */
    public function modifierMdp(Request $request)
    {
        $request->validate([
            'ancien_pin' => 'required|string|size:4|regex:/^\d{4}$/',
            'nouveau_pin' => 'required|string|size:4|regex:/^\d{4}$/',
        ]);

        $user = auth()->user();
        /** @var \App\Models\User $user */

        if (!$user) {
            return $this->errorResponse('Utilisateur non authentifié.', 401);
        }

        // Vérifier l'ancien PIN (support pour PINs hachés et non hachés)
        $pinValid = false;
        if (str_starts_with($user->pin, '$2y$')) {
            // PIN haché
            $pinValid = Hash::check($request->ancien_pin, $user->pin);
        } else {
            // PIN en texte clair
            $pinValid = $user->pin === $request->ancien_pin;
        }

        if (!$pinValid) {
            return $this->errorResponse('Ancien PIN incorrect.', 401);
        }

        // Mettre à jour le nouveau PIN
        $user->update(['pin' => $request->nouveau_pin]);

        return $this->successResponse(message: 'Mot de passe modifié avec succès.');
    }
}