<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Models\Client;
use App\Http\Resources\CompteResource;
use App\Http\Requests\StoreCompteRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Events\CompteCreated;

/**
 * @OA\Schema(
 *     schema="Pagination",
 *     type="object",
 *     title="Pagination",
 *     description="Informations de pagination",
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="per_page", type="integer", example=10),
 *     @OA\Property(property="total", type="integer", example=50),
 *     @OA\Property(property="last_page", type="integer", example=5),
 *     @OA\Property(property="from", type="integer", example=1),
 *     @OA\Property(property="to", type="integer", example=10)
 * )
 *
 * @OA\Schema(
 *     schema="Links",
 *     type="object",
 *     title="Links",
 *     description="Liens de navigation",
 *     @OA\Property(property="first", type="string", example="http://api.banque.example.com/api/v1/comptes?page=1"),
 *     @OA\Property(property="last", type="string", example="http://api.banque.example.com/api/v1/comptes?page=5"),
 *     @OA\Property(property="prev", type="string", nullable=true, example=null),
 *     @OA\Property(property="next", type="string", example="http://api.banque.example.com/api/v1/comptes?page=2")
 * )
 *
 * @OA\Schema(
 *     schema="Compte",
 *     type="object",
 *     title="Compte",
 *     description="Objet représentant un compte bancaire",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="numeroCompte", type="string", example="C-20251025-ABCD"),
 *     @OA\Property(property="titulaire", type="string", example="Mamadou Diallo"),
 *     @OA\Property(property="type", type="string", enum={"courant", "epargne", "bloqué", "cheque"}, example="cheque"),
 *     @OA\Property(property="solde", type="number", format="float", example=500000),
 *     @OA\Property(property="devise", type="string", enum={"FCFA", "XOF", "EUR", "USD"}, example="FCFA"),
 *     @OA\Property(property="dateCreation", type="string", format="date-time", example="2025-10-25T17:33:20Z"),
 *     @OA\Property(property="statut", type="string", enum={"actif", "inactif", "fermé"}, example="actif"),
 *     @OA\Property(property="motifBlocage", type="string", nullable=true, example=null),
 *     @OA\Property(property="metadata", type="object",
 *         @OA\Property(property="derniereModification", type="string", format="date-time", example="2025-10-25T17:33:20Z"),
 *         @OA\Property(property="version", type="integer", example=1)
 *     )
 * )
 */
class CompteController extends Controller
{
    use ApiResponseTrait;

    /**
     * Lister tous les comptes (Admin) ou les comptes du client connecté
     *
     * @OA\Get(
     *     path="/api/v1/comptes",
     *     summary="Lister les comptes",
     *     tags={"Comptes"},
     *     security={{"sanctum":{}}},
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
     *         description="Filtrer par type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"courant", "epargne", "bloqué", "cheque"})
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut",
     *         required=false,
     *         @OA\Schema(type="string", enum={"actif", "inactif", "fermé"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche par titulaire ou numéro",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"dateCreation", "solde", "titulaire"})
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Ordre",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Compte")),
     *             @OA\Property(property="pagination", ref="#/components/schemas/Pagination"),
     *             @OA\Property(property="links", ref="#/components/schemas/Links")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Validation des paramètres
        $validated = $request->validate([
            'page' => 'integer|min:1',
            'limit' => 'integer|min:1|max:100',
            'type' => ['nullable', Rule::in(['courant', 'epargne', 'bloqué', 'cheque'])],
            'statut' => ['nullable', Rule::in(['actif', 'inactif', 'fermé'])],
            'search' => 'nullable|string|max:255',
            'sort' => ['nullable', Rule::in(['dateCreation', 'solde', 'titulaire'])],
            'order' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $query = Compte::with('client');

        // Si c'est un client, filtrer par ses comptes
        if ($user->type === 'client') {
            $query->where('titulaire', $user->id);
        }

        // Appliquer les filtres
        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (!empty($validated['statut'])) {
            $query->where('statut', $validated['statut']);
        }

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('numCompte', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($clientQuery) use ($search) {
                      $clientQuery->where('nom', 'like', "%{$search}%")
                                  ->orWhere('prenom', 'like', "%{$search}%");
                  });
            });
        }

        // Appliquer le tri
        $sortField = match ($validated['sort'] ?? 'dateCreation') {
            'dateCreation' => 'date_creation',
            'solde' => 'created_at', // Tri par date de création comme approximation du solde
            'titulaire' => 'titulaire',
            default => 'date_creation',
        };

        $order = $validated['order'] ?? 'desc';
        $query->orderBy($sortField, $order);

        // Pagination
        $perPage = $validated['limit'] ?? 10;
        $comptes = $query->paginate($perPage);

        return $this->paginatedResponse($comptes, CompteResource::class);
    }

    /**
     * Lister les comptes archivés (Admin seulement)
     *
     * @OA\Get(
     *     path="/api/v1/comptes/archives",
     *     summary="Lister les comptes archivés",
     *     tags={"Comptes"},
     *     security={{"sanctum":{}}},
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
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes archivés",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Compte")),
     *             @OA\Property(property="pagination", ref="#/components/schemas/Pagination"),
     *             @OA\Property(property="links", ref="#/components/schemas/Links")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=403, description="Accès refusé - Admin requis")
     * )
     */
    public function archives(Request $request)
    {
        $user = Auth::user();

        if ($user->type !== 'admin') {
            return $this->errorResponse('Accès refusé. Réservé aux administrateurs.', 403);
        }

        // Validation des paramètres
        $validated = $request->validate([
            'page' => 'integer|min:1',
            'limit' => 'integer|min:1|max:100',
        ]);

        // Récupérer les comptes archivés depuis le cloud
        $comptes = Compte::getArchivedFromCloud($validated['limit'] ?? 10);

        return $this->paginatedResponse($comptes, CompteResource::class, 'Comptes archivés récupérés depuis le cloud');
    }

    /**
     * Créer un nouveau compte
     *
     * @OA\Post(
     *     path="/api/v1/comptes",
     *     summary="Créer un nouveau compte",
     *     tags={"Comptes"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type", "soldeInitial", "devise", "client"},
     *             @OA\Property(property="type", type="string", enum={"cheque", "courant", "epargne", "bloqué"}, example="cheque"),
     *             @OA\Property(property="soldeInitial", type="number", minimum=10000, example=500000),
     *             @OA\Property(property="devise", type="string", enum={"FCFA", "XOF", "EUR", "USD"}, example="FCFA"),
     *             @OA\Property(property="solde", type="number", example=10000),
     *             @OA\Property(property="client", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", nullable=true, example=null),
     *                 @OA\Property(property="titulaire", type="string", example="Hawa BB Wane"),
     *                 @OA\Property(property="email", type="string", format="email", example="cheikh.sy@example.com"),
     *                 @OA\Property(property="telephone", type="string", example="+221771234567"),
     *                 @OA\Property(property="adresse", type="string", example="Dakar, Sénégal"),
     *                 @OA\Property(property="nci", type="string", example="1234567890123A", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte créé avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Compte")
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
    public function store(StoreCompteRequest $request)
    {
        $validated = $request->validated();

        // Vérifier si le client existe
        $client = null;
        if (!empty($validated['client']['id'])) {
            $client = Client::find($validated['client']['id']);
            if (!$client) {
                return $this->errorResponse('Client non trouvé.', 404);
            }
        } else {
            // Créer un nouveau client
            $password = Str::random(8);
            $code = Str::random(6);

            $client = Client::create([
                'nom' => explode(' ', $validated['client']['titulaire'])[0] ?? $validated['client']['titulaire'],
                'prenom' => explode(' ', $validated['client']['titulaire'])[1] ?? '',
                'telephone' => $validated['client']['telephone'],
                'adresse' => $validated['client']['adresse'],
                'email' => $validated['client']['email'],
                'password' => Hash::make($password),
                'type' => 'client',
                'code_verification' => $code,
            ]);
        }

        // Créer le compte
        $compte = Compte::create([
            'titulaire' => $client->id,
            'type' => $validated['type'],
            'statut' => 'actif',
            'date_creation' => now(),
        ]);

        // Créer une transaction initiale pour le solde
        $compte->transactions()->create([
            'type' => 'depot',
            'montant' => $validated['soldeInitial'],
            'description' => 'Solde initial',
            'date_transaction' => now(),
        ]);

        // Déclencher l'événement
        event(new CompteCreated($compte, $client, $password ?? null, $code ?? null));

        return $this->successResponse(
            new CompteResource($compte),
            'Compte créé avec succès',
            201
        );
    }

    /**
     * Afficher un compte spécifique
     *
     * @OA\Get(
     *     path="/api/v1/comptes/{id}",
     *     summary="Afficher un compte spécifique",
     *     tags={"Comptes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du compte",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails du compte",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Compte")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $compte = Compte::with('client')->findOrFail($id);

        // Vérifier les permissions
        if ($user->type === 'client' && $compte->titulaire !== $user->id) {
            return $this->errorResponse('Accès refusé à ce compte.', 403);
        }

        return $this->successResponse(new CompteResource($compte));
    }

    /**
     * Mettre à jour un compte
     *
     * @OA\Put(
     *     path="/api/v1/comptes/{id}",
     *     summary="Mettre à jour un compte",
     *     tags={"Comptes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du compte",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", enum={"courant", "epargne", "bloqué", "cheque"}),
     *             @OA\Property(property="statut", type="string", enum={"actif", "inactif", "fermé"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte mis à jour avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte mis à jour avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Compte")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();

        // Vérifier les permissions (seulement admin peut modifier)
        if ($user->type !== 'admin') {
            return $this->errorResponse('Seul un administrateur peut modifier un compte.', 403);
        }

        $compte = Compte::findOrFail($id);

        $validated = $request->validate([
            'type' => ['sometimes', 'in:courant,epargne,bloqué,cheque'],
            'statut' => ['sometimes', 'in:actif,inactif,fermé'],
        ]);

        $compte->update($validated);

        return $this->successResponse(
            new CompteResource($compte),
            'Compte mis à jour avec succès'
        );
    }

    /**
     * Supprimer un compte (Archivage)
     *
     * @OA\Delete(
     *     path="/api/v1/comptes/{id}",
     *     summary="Supprimer un compte (Archivage)",
     *     tags={"Comptes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du compte",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte archivé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte archivé avec succès")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function destroy(string $id)
    {
        $user = Auth::user();

        // Vérifier les permissions (seulement admin peut supprimer)
        if ($user->type !== 'admin') {
            return $this->errorResponse('Seul un administrateur peut archiver un compte.', 403);
        }

        $compte = Compte::findOrFail($id);

        // Archiver le compte (soft delete)
        $compte->update(['statut' => 'fermé']);

        return $this->successResponse(
            message: 'Compte archivé avec succès'
        );
    }
}