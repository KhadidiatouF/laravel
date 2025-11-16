<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Info(
 *     title="OmPay API",
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
     *     description="Permet à un utilisateur (admin ou client) de se connecter avec son numéro de téléphone et son PIN",
     *     operationId="login",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone", "pin"},
     *             @OA\Property(property="telephone", type="string", example="+221771234567"),
     *             @OA\Property(property="pin", type="string", example="1234", description="Code PIN de l'utilisateur")
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
      *                 @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
      *                 @OA\Property(property="refresh_token", type="string", example="refresh_token_example"),
      *                 @OA\Property(property="token_type", type="string", example="Bearer"),
      *                 @OA\Property(property="expires_in", type="integer", example=3600)
      *             )
      *         )
      *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Numéro de téléphone introuvable ou PIN invalide",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Numéro de téléphone introuvable ou PIN invalide")
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
            Log::info('Téléphone reçu: ' . $request->telephone);

            $request->validate([
                'telephone' => 'required|string',
                'pin' => 'required|string|size:4|regex:/^\d{4}$/',
            ]);

            Log::info('Validation passée pour téléphone: ' . $request->telephone . ' avec PIN');

        // Essayer d'abord de trouver un admin
        try {
            $user = User::where('telephone', $request->telephone)->where('type', 'admin')->first();
            Log::info('Admin trouvé: ' . ($user ? 'Oui' : 'Non'));
        } catch (\Exception $e) {
            Log::error('Erreur lors de la recherche d\'admin pour ' . $request->telephone . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur interne lors de la recherche d\'utilisateur'
            ], 500);
        }

        // Si pas trouvé, chercher un client
        if (!$user) {
            try {
                $user = User::where('telephone', $request->telephone)->where('type', 'client')->first();
                Log::info('Client trouvé: ' . ($user ? 'Oui' : 'Non'));
            } catch (\Exception $e) {
                Log::error('Erreur lors de la recherche de client pour ' . $request->telephone . ': ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur interne lors de la recherche d\'utilisateur'
                ], 500);
            }
        }

        if (!$user) {
            Log::error('Échec de connexion: Numéro de téléphone introuvable pour ' . $request->telephone);
            return response()->json([
                'success' => false,
                'message' => 'Numéro de téléphone introuvable'
            ], 401);
        }

        // Vérifier le PIN
        $pinValid = false;
        if (str_starts_with($user->pin, '$2y$')) {
            // PIN haché
            $pinValid = Hash::check($request->pin, $user->pin);
        } else {
            // PIN en texte clair
            $pinValid = $user->pin === $request->pin;
        }

        if (!$pinValid) {
            Log::warning('PIN invalide pour téléphone: ' . $request->telephone);
            return response()->json([
                'success' => false,
                'message' => 'PIN invalide'
            ], 401);
        }

        Log::info('Connexion autorisée pour téléphone: ' . $request->telephone);

        // Créer le token d'accès
        try {
            Log::info('=== DÉBUT CRÉATION TOKEN ===');
            Log::info('Utilisateur: ' . $user->telephone . ' (ID: ' . $user->id . ', Type: ' . $user->type . ')');

            // Vérifier la base de données
            try {
                $userFromDb = \App\Models\User::find($user->id);
                Log::info('Utilisateur trouvé en DB: ' . ($userFromDb ? 'Oui' : 'Non'));
            } catch (\Exception $dbException) {
                Log::error('Erreur DB: ' . $dbException->getMessage());
            }

            // Vérifier les clés Passport
            $privateKeyPath = storage_path('oauth-private.key');
            $publicKeyPath = storage_path('oauth-public.key');
            Log::info('Clés Passport - Private: ' . (file_exists($privateKeyPath) ? 'Existe' : 'Manquant'));
            Log::info('Clés Passport - Public: ' . (file_exists($publicKeyPath) ? 'Existe' : 'Manquant'));

            if (!file_exists($privateKeyPath) || !file_exists($publicKeyPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuration Passport incomplète - clés manquantes',
                    'debug' => [
                        'private_key_exists' => file_exists($privateKeyPath),
                        'public_key_exists' => file_exists($publicKeyPath)
                    ]
                ], 500);
            }

            // Vérifier les clients OAuth
            try {
                $clientsCount = DB::table('oauth_clients')->count();
                Log::info('Nombre de clients OAuth: ' . $clientsCount);
            } catch (\Exception $e) {
                Log::error('Erreur lors du comptage des clients OAuth: ' . $e->getMessage());
                $clientsCount = 0;
            }

            try {
                $personalClientsCount = DB::table('oauth_personal_access_clients')->count();
                Log::info('Nombre de clients personnels: ' . $personalClientsCount);
            } catch (\Exception $e) {
                Log::error('Erreur lors du comptage des clients personnels: ' . $e->getMessage());
                $personalClientsCount = 0;
            }

            // Créer le token
            Log::info('Tentative de création du token...');
            try {
                $token = $user->createToken('API TOKEN');
                Log::info('Token créé avec succès!');
            } catch (\Exception $tokenException) {
                Log::error('Erreur lors de la création du token: ' . $tokenException->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la création du token d\'accès',
                    'debug' => [
                        'error' => $tokenException->getMessage(),
                        'user_id' => $user->id,
                        'user_type' => $user->type
                    ]
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie',
                'data' => [
                    'access_token' => $token->accessToken,
                    'refresh_token' => \Illuminate\Support\Str::random(64),
                    'token_type' => 'Bearer',
                    'expires_in' => 3600,
                ]
            ], 200, [], JSON_UNESCAPED_SLASHES);

        } catch (\Exception $e) {
            Log::error('=== ERREUR CRÉATION TOKEN ===');
            Log::error('Message: ' . $e->getMessage());
            Log::error('Classe: ' . get_class($e));
            Log::error('Fichier: ' . $e->getFile() . ':' . $e->getLine());
            Log::error('Trace: ' . $e->getTraceAsString());

            // Informations de diagnostic
            $debugInfo = [
                'user_id' => $user->id ?? 'N/A',
                'user_telephone' => $user->telephone ?? 'N/A',
                'user_type' => $user->type ?? 'N/A',
                'private_key_exists' => file_exists(storage_path('oauth-private.key')),
                'public_key_exists' => file_exists(storage_path('oauth-public.key')),
            ];

            try {
                $debugInfo['oauth_clients_count'] = DB::table('oauth_clients')->count();
                $debugInfo['personal_clients_count'] = DB::table('oauth_personal_access_clients')->count();
                $debugInfo['user_tokens_count'] = DB::table('oauth_access_tokens')->where('user_id', $user->id)->count();
            } catch (\Exception $dbError) {
                $debugInfo['db_error'] = $dbError->getMessage();
            }

            // Retourner les informations de debug dans la réponse pour diagnostic
            return response()->json([
                'success' => false,
                'message' => 'Erreur interne lors de la création du token',
                'debug' => $debugInfo,
                'error_details' => [
                    'message' => $e->getMessage(),
                    'class' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'access_token' => $token->accessToken,
                'refresh_token' => \Illuminate\Support\Str::random(64),
                'token_type' => 'Bearer',
                'expires_in' => 3600, // 1 heure
            ]
        ], 200, [], JSON_UNESCAPED_SLASHES);
        } catch (\Exception $e) {
            Log::error('Erreur générale dans la méthode login pour ' . $request->telephone . ': ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Erreur interne du serveur'
            ], 500);
        }
    }

    public function diagnostic()
    {
        try {
            $diagnostic = [
                'database_connection' => 'OK',
                'users_table_exists' => \Illuminate\Support\Facades\Schema::hasTable('users'),
                'oauth_clients_table_exists' => \Illuminate\Support\Facades\Schema::hasTable('oauth_clients'),
                'oauth_access_tokens_table_exists' => \Illuminate\Support\Facades\Schema::hasTable('oauth_access_tokens'),
                'oauth_personal_access_clients_table_exists' => \Illuminate\Support\Facades\Schema::hasTable('oauth_personal_access_clients'),
                'users_count' => \App\Models\User::count(),
                'oauth_clients_count' => \Illuminate\Support\Facades\DB::table('oauth_clients')->count(),
                'personal_access_clients_count' => \Illuminate\Support\Facades\DB::table('oauth_personal_access_clients')->count(),
                'admin_user_exists' => \App\Models\User::where('email', 'admin@example.com')->exists(),
                'passport_keys_exist' => [
                    'private' => file_exists(storage_path('oauth-private.key')),
                    'public' => file_exists(storage_path('oauth-public.key'))
                ]
            ];

            // Vérifier l'utilisateur admin spécifiquement
            $adminUser = \App\Models\User::where('email', 'admin@example.com')->first();
            if ($adminUser) {
                $diagnostic['admin_user_details'] = [
                    'id' => $adminUser->id,
                    'nom' => $adminUser->nom,
                    'prenom' => $adminUser->prenom,
                    'type' => $adminUser->type,
                    'tokens_count' => $adminUser->tokens()->count()
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Diagnostic système',
                'data' => $diagnostic
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du diagnostic',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function testEmail()
    {
        try {
            \Illuminate\Support\Facades\Log::info('=== TEST EMAIL ===');
            \Illuminate\Support\Facades\Log::info('Mailer: ' . config('mail.default'));
            \Illuminate\Support\Facades\Log::info('Host: ' . config('mail.mailers.smtp.host'));

            \Illuminate\Support\Facades\Mail::raw('Test Gmail SMTP - Ceci est un test depuis Laravel 🚀', function ($message) {
                $message->to('jamiral2019@gmail.com')
                        ->subject('Test Gmail SMTP - Laravel');
            });

            \Illuminate\Support\Facades\Log::info('✅ Email de test envoyé');

            return response()->json([
                'success' => true,
                'message' => 'Email de test envoyé avec succès',
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host')
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('❌ Erreur test email: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test email',
                'error' => $e->getMessage(),
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host')
            ], 500);
        }
    }
}
