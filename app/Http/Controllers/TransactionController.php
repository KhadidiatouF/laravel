<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransactionResource;
use App\Http\Resources\CompteResource;
use App\Http\Services\TransactionService;
use App\Http\Services\CompteService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="TransactionPagination",
 *     type="object",
 *     title="Pagination des Transactions",
 *     description="Informations de pagination pour les transactions",
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="per_page", type="integer", example=10),
 *     @OA\Property(property="total", type="integer", example=50),
 *     @OA\Property(property="last_page", type="integer", example=5),
 *     @OA\Property(property="from", type="integer", example=1),
 *     @OA\Property(property="to", type="integer", example=10)
 * )
 *
 * @OA\Schema(
 *     schema="TransactionLinks",
 *     type="object",
 *     title="Liens de navigation des Transactions",
 *     description="Liens de navigation pour les transactions",
 *     @OA\Property(property="first", type="string", example="http://api.banque.example.com/api/v1/transactions?page=1"),
 *     @OA\Property(property="last", type="string", example="http://api.banque.example.com/api/v1/transactions?page=5"),
 *     @OA\Property(property="prev", type="string", nullable=true, example=null),
 *     @OA\Property(property="next", type="string", example="http://api.banque.example.com/api/v1/transactions?page=2")
 * )
 */
class TransactionController extends Controller
{
    use ApiResponseTrait;

    protected $transactionService;
    protected $compteService;

    public function __construct(TransactionService $transactionService, CompteService $compteService)
    {
        $this->transactionService = $transactionService;
        $this->compteService = $compteService;
    }

    /**
     * Lister toutes les transactions (Admin) ou les transactions du client connecté
     *
     * @OA\Get(
     *     path="/api/v1/transactions",
     *     summary="Lister les transactions",
     *     tags={"Transactions"},
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
     *         description="Filtrer par type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"depot", "retrait", "transfert", "payement"})
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut",
     *         required=false,
     *         @OA\Schema(type="string", enum={"en_cours", "validee", "rejete", "annule"})
     *     ),
     *     @OA\Parameter(
     *         name="compte_id",
     *         in="query",
     *         description="Filtrer par compte",
     *         required=false,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="date_debut",
     *         in="query",
     *         description="Date de début (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_fin",
     *         in="query",
     *         description="Date de fin (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des transactions",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                     @OA\Property(property="numeroTransaction", type="string", example="TXN-20251102-ABCD"),
     *                     @OA\Property(property="type", type="string", enum={"depot", "retrait", "transfert", "payement"}, example="depot"),
     *                     @OA\Property(property="montant", type="number", format="float", example=50000),
     *                     @OA\Property(property="description", type="string", nullable=true, example="Dépôt d'espèces"),
     *                     @OA\Property(property="statut", type="string", enum={"en_cours", "validee", "rejete", "annule"}, example="validee"),
     *                     @OA\Property(property="dateTransaction", type="string", format="date-time", example="2025-11-02T10:30:00Z"),
     *                     @OA\Property(property="compteSource", type="object",
     *                         @OA\Property(property="id", type="string", format="uuid", example="3fa85f64-5717-4562-b3fc-2c963f66afa6"),
     *                         @OA\Property(property="numeroCompte", type="string", example="C-20251102-ABCD"),
     *                         @OA\Property(property="telephone", type="string", example="+221771234567")
     *                     ),
     *                     @OA\Property(property="compteDestination", type="object", nullable=true,
     *                         @OA\Property(property="id", type="string", format="uuid", example="3fa85f64-5717-4562-b3fc-2c963f66afa6"),
     *                         @OA\Property(property="numeroCompte", type="string", example="C-20251102-EFGH"),
     *                         @OA\Property(property="telephone", type="string", example="+221788839933")
     *                     ),
     *                     @OA\Property(property="metadata", type="object",
     *                         @OA\Property(property="derniereModification", type="string", format="date-time", example="2025-11-02T10:30:00Z"),
     *                         @OA\Property(property="version", type="integer", example=1)
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="pagination", ref="#/components/schemas/TransactionPagination"),
     *             @OA\Property(property="links", ref="#/components/schemas/TransactionLinks")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function index(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return $this->errorResponse('Utilisateur non authentifié.', 401);
        }

        // Validation des paramètres
        $validated = $request->validate([
            'page' => 'integer|min:1',
            'limit' => 'integer|min:1|max:100',
            'type' => ['nullable', Rule::in(['depot', 'retrait', 'transfert', 'payement'])],
            'statut' => ['nullable', Rule::in(['en_cours', 'validee', 'rejete', 'annule'])],
            'compte_id' => 'nullable|uuid|exists:comptes,id',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date',
        ]);

        $filters = array_filter($validated, fn($value) => !is_null($value));

        // Test temporaire : vérifier si le service fonctionne
        try {
            $repo = $this->transactionService->getTransactionRepository();
            $transactions = $repo->getAll($filters);
            return $this->paginatedResponse($transactions, TransactionResource::class);
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur repository: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Afficher une transaction spécifique
     *
     * @OA\Get(
     *     path="/api/v1/transactions/{id}",
     *     summary="Afficher une transaction spécifique",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la transaction",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de la transaction",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="numeroTransaction", type="string", example="TXN-20251102-ABCD"),
     *                 @OA\Property(property="type", type="string", enum={"depot", "retrait", "transfert", "payement"}, example="depot"),
     *                 @OA\Property(property="montant", type="number", format="float", example=50000),
     *                 @OA\Property(property="description", type="string", nullable=true, example="Dépôt d'espèces"),
     *                 @OA\Property(property="statut", type="string", enum={"en_cours", "validee", "rejete", "annule"}, example="validee"),
     *                 @OA\Property(property="dateTransaction", type="string", format="date-time", example="2025-11-02T10:30:00Z"),
     *                 @OA\Property(property="compteSource", type="object",
     *                     @OA\Property(property="id", type="string", format="uuid", example="3fa85f64-5717-4562-b3fc-2c963f66afa6"),
     *                     @OA\Property(property="numeroCompte", type="string", example="C-20251102-ABCD"),
     *                     @OA\Property(property="telephone", type="string", example="+221771234567")
     *                 ),
     *                 @OA\Property(property="compteDestination", type="object", nullable=true,
     *                     @OA\Property(property="id", type="string", format="uuid", example="3fa85f64-5717-4562-b3fc-2c963f66afa6"),
     *                     @OA\Property(property="numeroCompte", type="string", example="C-20251102-EFGH"),
     *                     @OA\Property(property="telephone", type="string", example="+221788839933")
     *                 ),
     *                 @OA\Property(property="metadata", type="object",
     *                     @OA\Property(property="derniereModification", type="string", format="date-time", example="2025-11-02T10:30:00Z"),
     *                     @OA\Property(property="version", type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Transaction non trouvée"),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function show(string $id)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return $this->errorResponse('Utilisateur non authentifié.', 401);
        }

        try {
            $transaction = $this->transactionService->getTransactionById($id, $user);
            return $this->successResponse(new TransactionResource($transaction));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 403);
        }
    }

    /**
     * Créer une nouvelle transaction
     *
     * @OA\Post(
     *     path="/api/v1/transactions",
     *     summary="Créer une nouvelle transaction",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     title="Dépôt",
     *                     required={"telephone", "type", "montant"},
     *                     @OA\Property(property="telephone", type="string", example="+221771234567"),
     *                     @OA\Property(property="type", type="string", enum={"depot"}, example="depot"),
     *                     @OA\Property(property="montant", type="number", minimum=100, example=50000),
     *                     @OA\Property(property="description", type="string", nullable=true, example="Dépôt d'espèces")
     *                 ),
     *                 @OA\Schema(
     *                     title="Retrait",
     *                     required={"telephone", "type", "montant"},
     *                     @OA\Property(property="telephone", type="string", example="+221771234567"),
     *                     @OA\Property(property="type", type="string", enum={"retrait"}, example="retrait"),
     *                     @OA\Property(property="montant", type="number", minimum=100, example=50000),
     *                     @OA\Property(property="description", type="string", nullable=true, example="Retrait d'espèces")
     *                 ),
     *                 @OA\Schema(
     *                     title="Transfert",
     *                     required={"telephone", "type", "montant", "numero_destinataire"},
     *                     @OA\Property(property="telephone", type="string", example="+221771234567"),
     *                     @OA\Property(property="type", type="string", enum={"transfert"}, example="transfert"),
     *                     @OA\Property(property="montant", type="number", minimum=100, example=50000),
     *                     @OA\Property(property="numero_destinataire", type="string", example="+221788839933"),
     *                     @OA\Property(property="description", type="string", nullable=true, example="Transfert vers un ami")
     *                 ),
     *                 @OA\Schema(
     *                     title="Paiement",
     *                     required={"telephone", "type", "montant", "numero_destinataire"},
     *                     @OA\Property(property="telephone", type="string", example="+221771234567"),
     *                     @OA\Property(property="type", type="string", enum={"payement"}, example="payement"),
     *                     @OA\Property(property="montant", type="number", minimum=100, example=50000),
     *                     @OA\Property(property="numero_destinataire", type="string", example="+221788839933"),
     *                     @OA\Property(property="code_marchand", type="string", nullable=true, example="MARCHAND123"),
     *                     @OA\Property(property="description", type="string", nullable=true, example="Paiement de facture")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Transaction créée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transaction créée avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="numeroTransaction", type="string", example="TXN-20251102-ABCD"),
     *                 @OA\Property(property="type", type="string", enum={"depot", "retrait", "transfert", "payement"}, example="depot"),
     *                 @OA\Property(property="montant", type="number", format="float", example=50000),
     *                 @OA\Property(property="description", type="string", nullable=true, example="Dépôt d'espèces"),
     *                 @OA\Property(property="statut", type="string", enum={"en_cours", "validee", "rejete", "annule"}, example="validee"),
     *                 @OA\Property(property="dateTransaction", type="string", format="date-time", example="2025-11-02T10:30:00Z"),
     *                 @OA\Property(property="compteSource", type="object",
     *                     @OA\Property(property="id", type="string", format="uuid", example="3fa85f64-5717-4562-b3fc-2c963f66afa6"),
     *                     @OA\Property(property="numeroCompte", type="string", example="C-20251102-ABCD"),
     *                     @OA\Property(property="telephone", type="string", example="+221771234567")
     *                 ),
     *                 @OA\Property(property="compteDestination", type="object", nullable=true,
     *                     @OA\Property(property="id", type="string", format="uuid", example="3fa85f64-5717-4562-b3fc-2c963f66afa6"),
     *                     @OA\Property(property="numeroCompte", type="string", example="C-20251102-EFGH"),
     *                     @OA\Property(property="telephone", type="string", example="+221788839933")
     *                 ),
     *                 @OA\Property(property="metadata", type="object",
     *                     @OA\Property(property="derniereModification", type="string", format="date-time", example="2025-11-02T10:30:00Z"),
     *                     @OA\Property(property="version", type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides ou solde insuffisant",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function store(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return $this->errorResponse('Utilisateur non authentifié.', 401);
        }

        // Validation de base
        $validated = $request->validate([
            'telephone' => 'required|string',
            'type' => 'required|in:depot,retrait,transfert,payement',
            'montant' => 'required|numeric|min:100',
            'description' => 'nullable|string|max:255',
        ]);

        // Validation conditionnelle selon le type de transaction
        if (in_array($validated['type'], ['transfert', 'payement'])) {
            $additionalValidation = $request->validate([
                'numero_destinataire' => 'nullable|string|required_if:type,transfert|required_without:code_marchand',
                'code_marchand' => 'nullable|string|required_if:type,payement|required_without:numero_destinataire',
            ]);
            $validated = array_merge($validated, $additionalValidation);
        }

        // Trouver le compte à partir du numéro de téléphone
        $client = \App\Models\Client::where('telephone', $validated['telephone'])->first();
        if (!$client) {
            return $this->errorResponse('Aucun compte trouvé pour ce numéro de téléphone.', 404);
        }

        // Vérifier que l'utilisateur a accès à ce compte
        if ($user->type === 'client' && $client->id !== $user->id) {
            return $this->errorResponse('Accès refusé à ce compte.', 403);
        }

        // Pour les dépôts et retraits, utiliser le premier compte actif du client
        if (in_array($validated['type'], ['depot', 'retrait'])) {
            $compte = $client->comptes()->where('statut', 'actif')->first();
            if (!$compte) {
                return $this->errorResponse('Aucun compte actif trouvé pour ce numéro de téléphone.', 404);
            }
            $validated['compte_id'] = $compte->id;
        }

        // Pour les transferts et paiements, trouver ou créer le compte destination
        if (in_array($validated['type'], ['transfert', 'payement'])) {
            // Pour les paiements, vérifier si c'est un numéro de téléphone ou un code marchand
            if ($validated['type'] === 'payement') {
                if (!empty($validated['numero_destinataire'])) {
                    // Paiement vers un numéro de téléphone
                    $destinataire = \App\Models\Client::where('telephone', $validated['numero_destinataire'])->first();
                    if (!$destinataire) {
                        return $this->errorResponse('Destinataire non trouvé.', 404);
                    }
                } elseif (!empty($validated['code_marchand'])) {
                    // Paiement vers un marchand (simulation - on pourrait avoir une table marchands)
                    // Pour l'instant, on simule en créant un compte marchand temporaire
                    $destinataire = \App\Models\Client::firstOrCreate(
                        ['telephone' => $validated['code_marchand']],
                        [
                            'nom' => 'Marchand',
                            'prenom' => $validated['code_marchand'],
                            'email' => $validated['code_marchand'] . '@marchand.local',
                            'password' => \Illuminate\Support\Facades\Hash::make('password'),
                            'type' => 'client'
                        ]
                    );
                } else {
                    return $this->errorResponse('Numéro de destinataire ou code marchand requis pour le paiement.', 400);
                }
            } else {
                // Transfert - toujours vers un numéro de téléphone
                $destinataire = \App\Models\Client::where('telephone', $validated['numero_destinataire'])->first();
                if (!$destinataire) {
                    return $this->errorResponse('Destinataire non trouvé.', 404);
                }
            }

            $compteDest = $destinataire->comptes()->where('statut', 'actif')->first();
            if (!$compteDest) {
                // Créer un compte actif pour le destinataire s'il n'en a pas
                $compteDest = $destinataire->comptes()->create([
                    'type' => 'courant',
                    'statut' => 'actif',
                    'date_creation' => now(),
                ]);
            }
            $validated['compte_destination_id'] = $compteDest->id;

            // Pour les transferts et paiements, utiliser le premier compte actif du client source
            $compte = $client->comptes()->where('statut', 'actif')->first();
            if (!$compte) {
                return $this->errorResponse('Aucun compte actif trouvé pour ce numéro de téléphone.', 404);
            }
            $validated['compte_id'] = $compte->id;
        }

        try {
            $transaction = $this->transactionService->createTransaction($validated, $user);
            return $this->successResponse(
                new TransactionResource($transaction),
                'Transaction créée avec succès',
                201
            );
        } catch (\Exception $e) {
            $statusCode = method_exists($e, 'getCode') && $e->getCode() >= 400 ? $e->getCode() : 400;
            return $this->errorResponse($e->getMessage(), $statusCode);
        }
    }

    public function update(Request $request, string $id)
    {
        $user = Auth::guard('api')->user();

        if ($user->type !== 'admin') {
            return $this->errorResponse('Seul un administrateur peut modifier le statut des transactions.', 403);
        }

        $validated = $request->validate([
            'statut' => 'required|in:en_cours,validee,rejete,annule',
        ]);

        $transaction = $this->transactionService->getTransactionById($id);
        $updatedTransaction = $this->transactionService->updateTransactionStatus($transaction, $validated['statut']);

        return $this->successResponse(
            new TransactionResource($updatedTransaction->load(['compte.client', 'compteDestination.client'])),
            'Statut de la transaction mis à jour avec succès'
        );
    }

    public function destroy(string $id)
    {
        $user = Auth::guard('api')->user();

        if ($user->type !== 'admin') {
            return $this->errorResponse('Seul un administrateur peut supprimer des transactions.', 403);
        }

        $transaction = $this->transactionService->getTransactionById($id);
        $this->transactionService->deleteTransaction($transaction);

        return $this->successResponse(message: 'Transaction supprimée avec succès');
    }

    public function adminStatistics()
    {
        $user = Auth::guard('api')->user();

        if ($user->type !== 'admin') {
            return $this->errorResponse('Accès refusé. Réservé aux administrateurs.', 403);
        }

        // Total des comptes existants
        $totalComptes = $this->compteService->listComptes([], 'created_at', 'desc', 1000)->total();

        // Statistiques des transactions
        $stats = $this->transactionService->getTransactionStatistics();

        // 10 dernières transactions
        $dernieresTransactions = $this->transactionService->getTransactions(['limit' => 10]);

        // Comptes créés aujourd'hui
        $comptesAujourdhui = $this->compteService->listComptes(
            ['date_creation' => today()->toDateString()],
            'created_at',
            'desc',
            100
        );

        return $this->successResponse([
            'totalComptes' => $totalComptes,
            'balanceGenerale' => $stats['balanceGenerale'],
            'totalTransactions' => $stats['totalTransactions'],
            'dernieresTransactions' => TransactionResource::collection($dernieresTransactions),
            'comptesAujourdhui' => CompteResource::collection($comptesAujourdhui),
        ]);
    }

    public function adminCompteDetails(string $id)
    {
        $user = Auth::guard('api')->user();

        if ($user->type !== 'admin') {
            return $this->errorResponse('Accès refusé. Réservé aux administrateurs.', 403);
        }

        $compte = $this->compteService->getCompteById($id);
        if (!$compte) {
            return $this->errorResponse('Compte non trouvé.', 404);
        }

        $compte->load('client');

        // Historique complet des transactions
        $historiqueTransactions = $this->transactionService->getTransactionsByCompte($id);

        // Statistiques du compte
        $statistiques = $this->transactionService->getCompteStatistics($id);

        return $this->successResponse([
            'compte' => new CompteResource($compte),
            'titulaire' => new \App\Http\Resources\ClientResource($compte->client),
            'historiqueTransactions' => TransactionResource::collection($historiqueTransactions),
            'statistiques' => $statistiques,
        ]);
    }

    public function clientCompteStatistiques(string $id)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return $this->errorResponse('Utilisateur non authentifié.', 401);
        }

        $compte = $this->compteService->getCompteById($id);
        if (!$compte) {
            return $this->errorResponse('Compte non trouvé.', 404);
        }

        // Vérifier que le compte appartient au client
        if (!$this->compteService->checkAccountAccess($compte, $user)) {
            return $this->errorResponse('Accès refusé à ce compte.', 403);
        }

        // Statistiques du compte
        $statistiques = $this->transactionService->getCompteStatistics($id);

        return $this->successResponse([
            'compte' => new CompteResource($compte),
            'statistiques' => $statistiques,
        ]);
    }

    public function clientDashboard()
    {
        $user = Auth::guard('api')->user();

        if (!$user || $user->type !== 'client') {
            return $this->errorResponse('Accès refusé. Réservé aux clients.', 403);
        }

        $dashboard = $this->transactionService->getClientDashboard($user);

        return $this->successResponse([
            'nombreComptes' => $dashboard['nombreComptes'],
            'balanceGlobale' => $dashboard['balanceGlobale'],
            'totalTransactions' => $dashboard['totalTransactions'],
            'dernieresTransactions' => TransactionResource::collection($dashboard['dernieresTransactions']),
            'comptes' => CompteResource::collection($dashboard['comptes']),
        ]);
    }

    public function clientCompteTransactions(Request $request, string $id)
    {
        $user = Auth::guard('api')->user();

        if (!$user || $user->type !== 'client') {
            return $this->errorResponse('Accès refusé. Réservé aux clients.', 403);
        }

        $compte = $this->compteService->getCompteById($id);
        if (!$compte) {
            return $this->errorResponse('Compte non trouvé.', 404);
        }

        // Vérifier que le compte appartient au client
        if (!$this->compteService->checkAccountAccess($compte, $user)) {
            return $this->errorResponse('Accès refusé à ce compte.', 403);
        }

        // Validation des paramètres
        $validated = $request->validate([
            'page' => 'integer|min:1',
            'limit' => 'integer|min:1|max:100',
        ]);

        // Transactions du compte
        $transactions = $this->transactionService->getTransactionsByCompte($id, [])
            ->paginate($validated['limit'] ?? 10);

        return $this->paginatedResponse($transactions, TransactionResource::class);
    }
}