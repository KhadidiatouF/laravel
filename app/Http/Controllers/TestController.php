<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @OA\Info(
 *     title="Banque API",
 *     version="1.0.0",
 *     description="API de gestion bancaire avec authentification Passport"
 * )
 *
 *   @OA\Server(
 *     url="https://khadidiatou-fall-api-laravel-0luq.onrender.com",
 *     description="Serveur de production"
 * )
 *
 * @OA\Server(
 *     url="http://127.0.0.1:8000",
 *     description="Serveur de développement"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Authentification désactivée pour les tests"
 * )
 */
class TestController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Authentification utilisateur",
     *     description="Permet à un utilisateur de se connecter et d'obtenir un token d'accès",
     *     operationId="login",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password")
     *         )
     *     ),
     * @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Identifiants incorrects",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Identifiants incorrects")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json(['message' => 'Utilisateur non trouvé'], 401);
            }

            if (!Hash::check($request->password, $user->password)) {
                return response()->json(['message' => 'Mot de passe incorrect'], 401);
            }

            // Générer un token simple pour les tests
            $token = hash('sha256', $user->id . time() . Str::random(32));

            return response()->json([
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'nom' => $user->nom,
                    'prenom' => $user->prenom,
                    'type' => $user->type
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            Log::error('Login error trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Erreur serveur: ' . $e->getMessage()], 500);
        }
    }
}
