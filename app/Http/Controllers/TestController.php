<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Info(
 *     title="Banque API",
 *     version="1.0.0",
 *     description="API de gestion bancaire avec authentification Passport"
 * )
 * * @OA\Server(
 *     url="https://khadidiatou-fall-api-laravel-0luq.onrender.com",
 *     description="Serveur de production"
 * )
 *
 * 
 * @OA\Server(
 *     url="http://127.0.0.1:8000",
 *     description="Serveur de développement"
 * )
 */
class TestController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Authentification utilisateur",
     *     description="Permet à un utilisateur (admin ou client) de se connecter et d'obtenir un token d'accès et un refresh token",
     *     operationId="login",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     title="Connexion Admin",
     *                     required={"email", "password"},
     *                     @OA\Property(property="email", type="string", format="email", example="admin@example.com"),
     *                     @OA\Property(property="password", type="string", format="password", example="password")
     *                 ),
     *                 @OA\Schema(
     *                     title="Connexion Client",
     *                     required={"email", "password", "codeSms"},
     *                     @OA\Property(property="email", type="string", format="email", example="client@example.com"),
     *                     @OA\Property(property="password", type="string", format="password", example="password"),
     *                     @OA\Property(property="codeSms", type="string", example="0AjbUW", description="Code de vérification SMS requis pour la première connexion")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Connexion réussie"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                     @OA\Property(property="nom", type="string", example="Admin"),
     *                     @OA\Property(property="prenom", type="string", example="System"),
     *                     @OA\Property(property="email", type="string", example="admin@example.com"),
     *                     @OA\Property(property="type", type="string", enum={"admin", "client"}, example="admin")
     *                 ),
     *             @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Code SMS requis pour la première connexion",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Code de vérification requis pour la première connexion")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Identifiants incorrects ou code SMS invalide",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Identifiants incorrects ou code SMS invalide")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données de requête invalides",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Les données fournies sont invalides"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        try {
            Log::info('=== DÉBUT DE LA MÉTHODE LOGIN ===');
            Log::info('Email reçu: ' . $request->email);

            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
                'codeSms' => 'nullable|string',
            ]);

            Log::info('Validation passée pour email: ' . $request->email);

        // Essayer d'abord de trouver un admin
        try {
            $user = User::where('email', $request->email)->where('type', 'admin')->first();
            Log::info('Admin trouvé: ' . ($user ? 'Oui' : 'Non'));
        } catch (\Exception $e) {
            Log::error('Erreur lors de la recherche d\'admin pour ' . $request->email . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur interne lors de la recherche d\'utilisateur'
            ], 500);
        }

        // Si pas trouvé, chercher un client
        if (!$user) {
            try {
                $user = User::where('email', $request->email)->where('type', 'client')->first();
                Log::info('Client trouvé: ' . ($user ? 'Oui' : 'Non'));
            } catch (\Exception $e) {
                Log::error('Erreur lors de la recherche de client pour ' . $request->email . ': ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur interne lors de la recherche d\'utilisateur'
                ], 500);
            }
        }

        if ($user) {
            Log::info('Type utilisateur: ' . $user->type);
            Log::info('Mot de passe haché: ' . $user->password);
            Log::info('Vérification mot de passe: ' . (Hash::check($request->password, $user->password) ? 'Succès' : 'Échec'));
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            Log::error('Échec de connexion: Identifiants incorrects pour ' . $request->email);
            return response()->json([
                'success' => false,
                'message' => 'Identifiants incorrects'
            ], 401);
        }

        // Pour les clients, vérifier le code SMS lors de la première connexion
        if ($user->type === 'client') {
            // Si c'est la première connexion (pas encore de token créé)
            $existingTokens = $user->tokens()->count();

            if ($existingTokens === 0) {
                // Première connexion - code SMS requis
                if (empty($request->codeSms)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Code de vérification requis pour la première connexion'
                    ], 400);
                }

                if ($request->codeSms !== $user->code_verification) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Code de vérification invalide'
                    ], 401);
                }

                // Marquer l'utilisateur comme vérifié (supprimer le code)
                $user->update(['code_verification' => null]);
            }
        }

        // Créer le token d'accès
        try {
            $token = $user->createToken('API TOKEN');
            Log::info('Token créé pour utilisateur: ' . $user->email . ', Token: ' . $token->accessToken);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du token pour ' . $user->email . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur interne lors de la création du token'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'nom' => $user->nom,
                    'prenom' => $user->prenom,
                    'email' => $user->email,
                    'type' => $user->type,
                ],
                'access_token' => $token->accessToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600, // 1 heure
            ]
        ], 200, [], JSON_UNESCAPED_SLASHES);
        } catch (\Exception $e) {
            Log::error('Erreur générale dans la méthode login pour ' . $request->email . ': ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Erreur interne du serveur'
            ], 500);
        }
    }
}
