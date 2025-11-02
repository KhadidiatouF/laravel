<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Http\Resources\ClientResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="Client",
 *     type="object",
 *     title="Client",
 *     description="Objet représentant un client",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="nom", type="string", example="Diallo"),
 *     @OA\Property(property="prenom", type="string", example="Mamadou"),
 *     @OA\Property(property="email", type="string", format="email", example="mamadou.diallo@example.com"),
 *     @OA\Property(property="telephone", type="string", example="+221771234567"),
 *     @OA\Property(property="adresse", type="string", example="Dakar, Sénégal"),
 *     @OA\Property(property="type", type="string", enum={"client"}, example="client"),
 *     @OA\Property(property="dateCreation", type="string", format="date-time", example="2025-10-25T17:33:20Z"),
 *     @OA\Property(property="metadata", type="object",
 *         @OA\Property(property="derniereModification", type="string", format="date-time", example="2025-10-25T17:33:20Z"),
 *         @OA\Property(property="version", type="integer", example=1)
 *     )
 * )
 */
class ClientController extends Controller
{
    use ApiResponseTrait;

    /**
     * Lister tous les clients (Admin seulement)
     *
     * @OA\Get(
     *     path="/api/v1/clients",
     *     summary="Lister les clients",
     *     tags={"Clients"},
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
     *         name="search",
     *         in="query",
     *         description="Recherche par nom, prénom, email ou téléphone",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="telephone",
     *         in="query",
     *         description="Filtrer par numéro de téléphone",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des clients",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Client")),
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
        $user = Auth::guard('api')->user();

        if ($user->type !== 'admin') {
            return $this->errorResponse('Accès refusé. Réservé aux administrateurs.', 403);
        }

        // Validation des paramètres
        $validated = $request->validate([
            'page' => 'integer|min:1',
            'limit' => 'integer|min:1|max:100',
            'search' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:20',
        ]);

        $query = Client::with('comptes');

        // Filtre par téléphone spécifique
        if (!empty($validated['telephone'])) {
            $query->where('telephone', $validated['telephone']);
        }

        // Recherche générale (nom, prénom, email, téléphone)
        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('telephone', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $validated['limit'] ?? 10;
        $clients = $query->paginate($perPage);

        return $this->paginatedResponse($clients, ClientResource::class);
    }

    /**
     * Afficher un client spécifique
     *
     * @OA\Get(
     *     path="/api/v1/clients/{id}",
     *     summary="Afficher un client spécifique",
     *     tags={"Clients"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du client",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails du client",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Client")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Client non trouvé"),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function show(string $id)
    {
        $user = Auth::guard('api')->user();
        $client = Client::with('comptes')->findOrFail($id);

        // Vérifier les permissions (admin ou client propriétaire)
        if ($user->type === 'client' && $client->id !== $user->id) {
            return $this->errorResponse('Accès refusé à ce client.', 403);
        }

        return $this->successResponse(new ClientResource($client));
    }

    /**
     * Créer un nouveau client
     *
     * @OA\Post(
     *     path="/api/v1/clients",
     *     summary="Créer un nouveau client",
     *     tags={"Clients"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom", "prenom", "email", "telephone", "adresse"},
     *             @OA\Property(property="nom", type="string", example="Diallo"),
     *             @OA\Property(property="prenom", type="string", example="Mamadou"),
     *             @OA\Property(property="email", type="string", format="email", example="mamadou.diallo@example.com"),
     *             @OA\Property(property="telephone", type="string", example="+221771234567"),
     *             @OA\Property(property="adresse", type="string", example="Dakar, Sénégal")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Client créé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Client créé avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Client")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="VALIDATION_ERROR"),
     *                 @OA\Property(property="message", type="string", example="Les données fournies sont invalides"),
     *                 @OA\Property(property="details", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function store(Request $request)
    {
        $user = Auth::guard('api')->user();

        if ($user->type !== 'admin') {
            return $this->errorResponse('Seul un administrateur peut créer un client.', 403);
        }

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'telephone' => 'required|string|unique:users,telephone',
            'adresse' => 'required|string|max:255',
        ]);

        $password = \Illuminate\Support\Str::random(8);
        $code = \Illuminate\Support\Str::random(6);

        $client = Client::create([
            'nom' => $validated['nom'],
            'prenom' => $validated['prenom'],
            'email' => $validated['email'],
            'telephone' => $validated['telephone'],
            'adresse' => $validated['adresse'],
            'password' => Hash::make($password),
            'type' => 'client',
            'code_verification' => $code,
        ]);

        return $this->successResponse(
            new ClientResource($client),
            'Client créé avec succès',
            201
        );
    }

    /**
     * Mettre à jour un client
     *
     * @OA\Put(
     *     path="/api/v1/clients/{id}",
     *     summary="Mettre à jour un client",
     *     tags={"Clients"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du client",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nom", type="string", example="Diallo"),
     *             @OA\Property(property="prenom", type="string", example="Mamadou"),
     *             @OA\Property(property="email", type="string", format="email", example="mamadou.diallo@example.com"),
     *             @OA\Property(property="telephone", type="string", example="+221771234567"),
     *             @OA\Property(property="adresse", type="string", example="Dakar, Sénégal")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client mis à jour avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Client mis à jour avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Client")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Client non trouvé"),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::guard('api')->user();
        $client = Client::findOrFail($id);

        // Vérifier les permissions (admin ou client propriétaire)
        if ($user->type === 'client' && $client->id !== $user->id) {
            return $this->errorResponse('Accès refusé à ce client.', 403);
        }

        $validated = $request->validate([
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($id)],
            'telephone' => ['sometimes', 'string', Rule::unique('users')->ignore($id)],
            'adresse' => 'sometimes|string|max:255',
        ]);

        $client->update($validated);

        return $this->successResponse(
            new ClientResource($client),
            'Client mis à jour avec succès'
        );
    }

    /**
     * Récupérer un client à partir du numéro téléphone
     *
     * @OA\Get(
     *     path="/api/v1/clients/telephone/{telephone}",
     *     summary="Récupérer un client à partir du numéro téléphone",
     *     tags={"Clients"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="telephone",
     *         in="path",
     *         required=true,
     *         description="Numéro de téléphone du client",
     *         @OA\Schema(type="string", example="+221771234567")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client trouvé",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Client")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Client non trouvé"),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=403, description="Accès refusé - Admin requis")
     * )
     */
    public function findByPhone(string $telephone)
    {
        $user = Auth::guard('api')->user();

        if ($user->type !== 'admin') {
            return $this->errorResponse('Accès refusé. Réservé aux administrateurs.', 403);
        }

        $client = Client::with('comptes')->where('telephone', $telephone)->first();

        if (!$client) {
            return $this->errorResponse('Client non trouvé avec ce numéro de téléphone.', 404);
        }

        return $this->successResponse(new ClientResource($client));
    }

    /**
     * Supprimer un client
     *
     * @OA\Delete(
     *     path="/api/v1/clients/{id}",
     *     summary="Supprimer un client",
     *     tags={"Clients"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du client",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client supprimé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Client supprimé avec succès")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Client non trouvé"),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function destroy(string $id)
    {
        $user = Auth::guard('api')->user();

        if ($user->type !== 'admin') {
            return $this->errorResponse('Seul un administrateur peut supprimer un client.', 403);
        }

        $client = Client::findOrFail($id);

        // Vérifier si le client a des comptes actifs
        if ($client->comptes()->where('statut', 'actif')->exists()) {
            return $this->errorResponse('Impossible de supprimer un client ayant des comptes actifs.', 400);
        }

        $client->delete();

        return $this->successResponse(
            message: 'Client supprimé avec succès'
        );
    }
}