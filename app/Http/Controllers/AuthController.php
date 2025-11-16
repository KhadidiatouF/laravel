<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransactionResource;
use App\Http\Services\TransactionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="Authentification",
 *     description="Endpoints d'authentification"
 * )
 */
class AuthController extends Controller
{
    use ApiResponseTrait;

    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Récupérer les informations de l'utilisateur connecté avec son compte et ses transactions
     *
     * @OA\Get(
     *     path="/api/v1/auth/me",
     *     summary="Informations de l'utilisateur connecté",
     *     tags={"Authentification"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page pour les transactions",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre de transactions par page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer les transactions par type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"depot", "retrait", "transfert", "payement"})
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer les transactions par statut",
     *         required=false,
     *         @OA\Schema(type="string", enum={"en_cours", "validee", "rejete", "annule"})
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
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Trier par champ",
     *         required=false,
     *         @OA\Schema(type="string", enum={"date_transaction", "montant", "type"}, default="date_transaction")
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Ordre de tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Informations de l'utilisateur récupérées avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="nom", type="string"),
     *                     @OA\Property(property="prenom", type="string"),
     *                     @OA\Property(property="email", type="string"),
     *                     @OA\Property(property="telephone", type="string")
     *                 ),
     *                 @OA\Property(property="compte", type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="numeroCompte", type="string"),
     *                     @OA\Property(property="solde", type="number", format="float")
     *                 ),
     *                 @OA\Property(property="transactions", type="object",
     *                     @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Transaction")),
     *                     @OA\Property(property="pagination", ref="#/components/schemas/TransactionPagination"),
     *                     @OA\Property(property="links", ref="#/components/schemas/TransactionLinks")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=404, description="Utilisateur ou compte non trouvé")
     * )
     */
    public function me(Request $request)
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
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date',
            'sort_by' => ['nullable', Rule::in(['date_transaction', 'montant', 'type'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        // Récupérer le compte de l'utilisateur (premier compte actif)
        $compte = $user->client?->comptes()->where('statut', 'actif')->first();

        if (!$compte) {
            return $this->errorResponse('Aucun compte actif trouvé pour cet utilisateur.', 404);
        }


        // Préparer les filtres pour les transactions
        $filters = array_filter([
            'compte_id' => $compte->id,
            'type' => $validated['type'] ?? null,
            'statut' => $validated['statut'] ?? null,
            'date_debut' => $validated['date_debut'] ?? null,
            'date_fin' => $validated['date_fin'] ?? null,
        ]);

        // Récupérer les transactions avec pagination et tri
        $sortBy = $validated['sort_by'] ?? 'date_transaction';
        $sortOrder = $validated['sort_order'] ?? 'desc';
        $limit = $validated['limit'] ?? 10;

        $transactions = $this->transactionService->getTransactionRepository()
            ->getAll($filters, $sortBy, $sortOrder, $limit);

        // Préparer la réponse
        $data = [
            'user' => [
                'id' => $user->id,
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'email' => $user->email,
                'telephone' => $user->telephone,
            ],
            'compte' => [
                'id' => $compte->id,
                'numeroCompte' => $compte->numCompte,
                'solde' => $compte->solde,
            ],
            'transactions' => $transactions,
        ];

        return $this->successResponse($data);
    }
}