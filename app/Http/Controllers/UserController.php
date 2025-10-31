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
     *     path="/api/v1/users",
     *     summary="Lister tous les utilisateurs",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type d'utilisateur",
     *         required=false,
     *         @OA\Schema(type="string", enum={"admin", "client"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche par nom, prénom ou email",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des utilisateurs",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/User")),
     *             @OA\Property(property="pagination", ref="#/components/schemas/Pagination"),
     *             @OA\Property(property="links", ref="#/components/schemas/Links")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=403, description="Accès refusé - Admin requis")
     * )
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Vérifier les permissions (seulement admin peut lister tous les utilisateurs)
        if ($user->type !== 'admin') {
            return $this->errorResponse('Accès refusé. Réservé aux administrateurs.', 403);
        }

        // Validation des paramètres
        $validated = $request->validate([
            'page' => 'integer|min:1',
            'limit' => 'integer|min:1|max:100',
            'type' => ['nullable', \Illuminate\Validation\Rule::in(['admin', 'client'])],
            'search' => 'nullable|string|max:255',
        ]);

        $query = User::query();

        // Appliquer les filtres
        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $validated['limit'] ?? 10;
        $users = $query->paginate($perPage);

        return $this->paginatedResponse($users);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/users",
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
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Utilisateur créé avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Données invalides"),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=403, description="Accès refusé - Admin requis")
     * )
     */
    public function store(Request $request)
    {
        $currentUser = auth()->user();

        // Vérifier les permissions (seulement admin peut créer des utilisateurs)
        if ($currentUser->type !== 'admin') {
            return $this->errorResponse('Seul un administrateur peut créer un utilisateur.', 403);
        }

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

        return $this->successResponse($user, 'Utilisateur créé avec succès', 201);
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
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Utilisateur non trouvé"),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function show($id)
    {
        $currentUser = auth()->user();

        // Vérifier les permissions (admin ou utilisateur propriétaire)
        if ($currentUser->type === 'client' && $currentUser->id !== $id) {
            return $this->errorResponse('Accès refusé à cet utilisateur.', 403);
        }

        $user = User::findOrFail($id);
        return $this->successResponse($user);
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
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Utilisateur mis à jour avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Utilisateur non trouvé"),
     *     @OA\Response(response=400, description="Données invalides"),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function update(Request $request, $id)
    {
        $currentUser = auth()->user();

        // Vérifier les permissions (admin ou utilisateur propriétaire)
        if ($currentUser->type === 'client' && $currentUser->id !== $id) {
            return $this->errorResponse('Accès refusé à cet utilisateur.', 403);
        }

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

        return $this->successResponse($user, 'Utilisateur mis à jour avec succès');
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{id}",
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