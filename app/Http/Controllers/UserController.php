<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Compte;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     description="Modèle utilisateur",
 *     @OA\Property(property="id", type="string", format="uuid", description="ID unique de l'utilisateur"),
 *     @OA\Property(property="nom", type="string", description="Nom de l'utilisateur"),
 *     @OA\Property(property="prenom", type="string", description="Prénom de l'utilisateur"),
 *     @OA\Property(property="email", type="string", format="email", description="Adresse email"),
 *     @OA\Property(property="telephone", type="string", description="Numéro de téléphone"),
 *     @OA\Property(property="adresse", type="string", description="Adresse"),
 *     @OA\Property(property="type", type="string", enum={"admin", "client"}, description="Type d'utilisateur"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class UserController extends Controller
{
    use ApiResponseTrait;
    /**
     * @OA\Get(
     *     path="/api/users",
     *     summary="Lister tous les utilisateurs",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des utilisateurs",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non autorisé")
     * )
     */
    public function index()
    {
        return User::all();
    }

    /**
     * @OA\Post(
     *     path="/api/users",
     *     summary="Créer un nouvel utilisateur",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom", "prenom", "email", "password", "type"},
     *             @OA\Property(property="nom", type="string"),
     *             @OA\Property(property="prenom", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="telephone", type="string"),
     *             @OA\Property(property="adresse", type="string"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="type", type="string", enum={"admin", "client"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Utilisateur créé",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(response=400, description="Données invalides"),
     *     @OA\Response(response=401, description="Non autorisé")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string|max:255',
            'password' => 'required|string|min:8',
            'type' => 'required|in:admin,client',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return response()->json($user, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/users/{id}",
     *     summary="Afficher un utilisateur spécifique",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'utilisateur",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de l'utilisateur",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(response=404, description="Utilisateur non trouvé"),
     *     @OA\Response(response=401, description="Non autorisé")
     * )
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    /**
     * @OA\Put(
     *     path="/api/users/{id}",
     *     summary="Mettre à jour un utilisateur",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'utilisateur",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nom", type="string"),
     *             @OA\Property(property="prenom", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="telephone", type="string"),
     *             @OA\Property(property="adresse", type="string"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="type", type="string", enum={"admin", "client"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Utilisateur mis à jour",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(response=404, description="Utilisateur non trouvé"),
     *     @OA\Response(response=400, description="Données invalides"),
     *     @OA\Response(response=401, description="Non autorisé")
     * )
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'nom' => 'sometimes|required|string|max:255',
            'prenom' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string|max:255',
            'password' => 'sometimes|required|string|min:8',
            'type' => 'sometimes|required|in:admin,client',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json($user);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/users/{id}",
     *     summary="Supprimer un utilisateur",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'utilisateur",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Utilisateur supprimé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Utilisateur supprimé avec succès")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Utilisateur non trouvé"),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function destroy($id)
    {
        $currentUser = auth()->user();

        // Vérifier les permissions (seulement admin peut supprimer)
        if ($currentUser->type !== 'admin') {
            return $this->errorResponse('Seul un administrateur peut supprimer un utilisateur.', 403);
        }

        $user = User::findOrFail($id);

        // Empêcher la suppression de son propre compte
        if ($user->id === $currentUser->id) {
            return $this->errorResponse('Vous ne pouvez pas supprimer votre propre compte.', 403);
        }

        // Supprimer tous les comptes associés à cet utilisateur
        $user->comptes()->delete();

        // Supprimer l'utilisateur
        $user->delete();

        return $this->successResponse(
            message: 'Utilisateur supprimé avec succès'
        );
    }
}