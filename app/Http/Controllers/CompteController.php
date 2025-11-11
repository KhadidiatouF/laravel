<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Models\Client;
use App\Http\Resources\CompteResource;
use App\Http\Requests\StoreCompteRequest;
use App\Traits\ApiResponseTrait;
use App\Mail\CompteCreatedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
 *     @OA\Property(property="type", type="string", enum={"courant", "epargne", "cheque"}, example="courant"),
 *     @OA\Property(property="solde", type="number", format="float", example=500000),
 *     @OA\Property(property="devise", type="string", enum={"FCFA", "XOF", "EUR", "USD"}, example="FCFA"),
 *     @OA\Property(property="dateCreation", type="string", format="date-time", example="2025-10-25T17:33:20Z"),
 *     @OA\Property(property="statut", type="string", enum={"actif", "inactif", "bloqué"}, example="actif"),
 *     @OA\Property(property="motifBlocage", type="string", nullable=true, example=null),
 *     @OA\Property(property="informationsBlocage", type="object", nullable=true,
 *         description="Informations de blocage pour les comptes épargne bloqués",
 *         @OA\Property(property="dateDebutBlocage", type="string", format="date-time", example="2025-10-30T12:00:00Z"),
 *         @OA\Property(property="dateFinBlocage", type="string", format="date-time", example="2025-11-02T12:00:00Z"),
 *         @OA\Property(property="dureeBlocageJours", type="integer", example=3, description="Durée du blocage en jours")
 *     ),
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
     *         @OA\Schema(type="string", enum={"courant", "epargne", "cheque"})
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut",
     *         required=false,
     *         @OA\Schema(type="string", enum={"actif", "inactif", "bloqué"})
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
        $user = Auth::guard('api')->user();

        \Illuminate\Support\Facades\Log::info('Utilisateur authentifié dans CompteController: ' . ($user ? $user->email : 'null'));

        if (!$user) {
            return $this->errorResponse('Utilisateur non authentifié.', 401);
        }

        $validated = $request->validate([
            'page' => 'integer|min:1',
            'limit' => 'integer|min:1|max:100',
            'statut' => ['nullable', Rule::in(['actif', 'inactif', 'bloqué'])],
            'search' => 'nullable|string',
            'sort' => ['nullable', Rule::in(['dateCreation', 'solde', 'titulaire'])],
            'order' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $query = Compte::with('client');

        // Si c'est un client, filtrer par ses comptes uniquement
        if ($user->type === 'client') {
            $query->where('titulaire', $user->id);
        } else {
            // Admin voit tous les comptes actifs par défaut
            $query->where('statut', 'actif');
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
            'solde' => 'created_at', 
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

    public function archives(Request $request)
    {
        $user = Auth::guard('api')->user();

        if ($user->type !== 'admin') {
            return $this->errorResponse('Accès refusé. Réservé aux administrateurs.', 403);
        }

        // Validation des paramètres
        $validated = $request->validate([
            'page' => 'integer|min:1',
            'limit' => 'integer|min:1|max:100',
        ]);

        // Récupérer les comptes archivés (statut = 'fermé')
        $comptes = Compte::withoutGlobalScope('nonSupprime')
            ->where('statut', 'fermé')
            ->with('client')
            ->paginate($validated['limit'] ?? 10);

        return $this->paginatedResponse($comptes, CompteResource::class, 'Comptes archivés récupérés avec succès');
    }

    /**
     * Créer un nouveau compte
     *
     * @OA\Post(
     *     path="/api/v1/comptes",
     *     summary="Créer un nouveau compte",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"soldeInitial", "devise", "client"},
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
     *             ),
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
        $user = Auth::guard('api')->user();
        $validated = $request->validated();

        // Vérifier si le client existe
        $client = null;
        if (!empty($validated['client']['id'])) {
            $client = Client::find($validated['client']['id']);
            if (!$client) {
                return $this->errorResponse('Client non trouvé.', 404);
            }
        } else {
            // Créer un nouveau client (seulement admin)
            if ($user->type !== 'admin') {
                return $this->errorResponse('Seul un administrateur peut créer un nouveau client.', 403);
            }

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

        // Calculer la durée du blocage si c'est un compte épargne
        $dureeBlocage = null;
        $dateDebutBlocage = null;
        $dateFinBlocage = null;
        $statutInitial = 'actif';

        // Par défaut, créer un compte courant
        $typeCompte = 'courant';

        // Si des dates de blocage sont fournies, créer un compte épargne
        if (!empty($validated['dateDebutBlocage']) && !empty($validated['dateFinBlocage'])) {
            $typeCompte = 'epargne';
            $dateDebutBlocage = $validated['dateDebutBlocage'];
            $dateFinBlocage = $validated['dateFinBlocage'];

            // Calculer la durée en jours
            $dateDebut = \Carbon\Carbon::parse($dateDebutBlocage);
            $dateFin = \Carbon\Carbon::parse($dateFinBlocage);
            $dureeBlocage = $dateDebut->diffInDays($dateFin);

            // Si la date de début de blocage est aujourd'hui ou dans le passé, bloquer immédiatement
            if ($dateDebut->isToday() || $dateDebut->isPast()) {
                $statutInitial = 'bloqué';
            }
        }

        // Créer le compte
        $compte = Compte::create([
            'titulaire' => $client->id,
            'type' => $typeCompte,
            'statut' => $statutInitial,
            'date_creation' => now(),
            'date_debut_bloquage' => $dateDebutBlocage ?? null,
            'date_fin_bloquage' => $dateFinBlocage ?? null,
            'duree_bloquage_jours' => $dureeBlocage ?? null,
        ]);

        // Créer une transaction initiale pour le solde
        $compte->transactions()->create([
            'type' => 'depot',
            'montant' => $validated['soldeInitial'],
            'description' => 'Solde initial',
            'date_transaction' => now(),
        ]);

        // Envoyer l'email de confirmation
        try {
            \Illuminate\Support\Facades\Log::info('=== ENVOI EMAIL ===');
            \Illuminate\Support\Facades\Log::info('Destinataire: ' . $client->email);
            \Illuminate\Support\Facades\Log::info('Mailer utilisé: ' . config('mail.default'));

            if (!empty($validated['client']['id'])) {
                // Client existant - pas de mot de passe généré
                \Illuminate\Support\Facades\Log::info('Type: Client existant');
                Mail::to($client->email)->send(new CompteCreatedMail($compte, $client));
            } else {
                // Nouveau client - inclure le mot de passe généré
                \Illuminate\Support\Facades\Log::info('Type: Nouveau client avec mot de passe généré');
                Mail::to($client->email)->send(new CompteCreatedMail($compte, $client, $password));
            }

            \Illuminate\Support\Facades\Log::info('Email envoyé avec succès à: ' . $client->email);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('Classe d\'erreur: ' . get_class($e));
            \Illuminate\Support\Facades\Log::error('Code d\'erreur: ' . $e->getCode());

            // Essayer avec Mail::raw() comme fallback
            try {
                \Illuminate\Support\Facades\Log::info('Tentative d\'envoi avec Mail::raw()');
                $messageContent = "Bienvenue {$client->prenom} {$client->nom}!\n\n";
                $messageContent .= "Votre compte bancaire a été créé avec succès.\n";
                $messageContent .= "Numéro de compte: {$compte->numCompte}\n";
                $messageContent .= "Type: " . ucfirst($compte->type) . "\n";
                $messageContent .= "Statut: " . ucfirst($compte->statut) . "\n\n";

                if ($password) {
                    $messageContent .= "Votre mot de passe temporaire: {$password}\n";
                    $messageContent .= "Veuillez le changer lors de votre première connexion.\n\n";
                }

                $messageContent .= "Cordialement,\nBanque API";

                Mail::raw($messageContent, function ($message) use ($client) {
                    $message->to($client->email)
                            ->subject('Votre compte bancaire a été créé - Banque API');
                });

                \Illuminate\Support\Facades\Log::info('Email de fallback envoyé avec succès');

            } catch (\Exception $fallbackError) {
                \Illuminate\Support\Facades\Log::error('Échec même du fallback: ' . $fallbackError->getMessage());
            }

            // Ne pas échouer la création du compte si l'email échoue
        }

        // Déclencher l'événement (seulement pour nouveau client)
        if (!empty($validated['client']['id'])) {
            event(new CompteCreated($compte, $client, null, null));
        } else {
            event(new CompteCreated($compte, $client, $password, $code));
        }

        return $this->successResponse(
            new CompteResource($compte),
            'Compte créé avec succès - Email envoyé à ' . $client->email,
            201
        );
    }

    public function show(string $numero)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return $this->errorResponse('Utilisateur non authentifié.', 401);
        }

        // Essayer d'abord de trouver par numéro de compte
        $compte = Compte::with('client')->where('numCompte', $numero)->first();

        // Si pas trouvé par numéro, essayer par ID (UUID)
        if (!$compte) {
            $compte = Compte::with('client')->find($numero);
        }

        // Si le compte n'est pas trouvé localement et qu'il pourrait être archivé
        if (!$compte) {
            // Simulation : vérifier dans Neon si c'est un compte épargne archivé
            // En production, cela ferait un appel API vers Neon
            // Pour la simulation, on suppose que le compte n'existe pas
            return $this->errorResponse('Compte non trouvé.', 404);
        }

        // Vérifier les permissions : client ne peut voir que ses propres comptes
        if ($user->type === 'client' && $compte->titulaire !== $user->id) {
            return $this->errorResponse('Accès refusé à ce compte.', 403);
        }

        // Admin ne peut voir que les comptes actifs par défaut
        if ($user->type === 'admin' && $compte->statut !== 'actif') {
            return $this->errorResponse('Seul un compte actif peut être consulté.', 403);
        }

        return $this->successResponse(new CompteResource($compte));
    }

    public function update(Request $request, string $id)
    {
        $user = Auth::guard('api')->user();
        $compte = Compte::findOrFail($id);

        // Vérifier les permissions
        if ($user->type === 'client') {
            // Client ne peut modifier que ses propres comptes
            if ($compte->titulaire !== $user->id) {
                return $this->errorResponse('Accès refusé à ce compte.', 403);
            }
            // Client ne peut modifier que le type (pas le statut)
            $validated = $request->validate([
                'type' => ['sometimes', 'in:courant,epargne,cheque'],
            ]);
        } else {
            // Admin peut tout modifier mais seulement sur les comptes actifs
            if ($compte->statut !== 'actif') {
                return $this->errorResponse('Seul un compte actif peut être modifié.', 403);
            }
            $validated = $request->validate([
                'type' => ['sometimes', 'in:courant,epargne,cheque'],
                'statut' => ['sometimes', 'in:actif,inactif,bloqué'],
            ]);
        }

        $compte->update($validated);

        return $this->successResponse(
            new CompteResource($compte),
            'Compte mis à jour avec succès'
        );
    }

    public function findByPhone(string $telephone)
    {
        $user = Auth::guard('api')->user();

        // Trouver le client par numéro de téléphone
        $client = Client::where('telephone', $telephone)->first();

        if (!$client) {
            return $this->errorResponse('Client non trouvé avec ce numéro de téléphone.', 404);
        }

        // Récupérer les comptes du client
        $comptes = Compte::with('client')
            ->where('titulaire', $client->id)
            ->where('statut', 'actif')
            ->get();

        // Vérifier les permissions : client ne peut voir que ses propres comptes
        if ($user->type === 'client' && $client->id !== $user->id) {
            return $this->errorResponse('Accès refusé aux comptes de ce client.', 403);
        }

        // Admin ne peut voir que les comptes actifs
        if ($user->type === 'admin' && $comptes->where('statut', '!=', 'actif')->count() > 0) {
            $comptes = $comptes->where('statut', 'actif');
        }

        if ($comptes->isEmpty()) {
            return $this->errorResponse('Aucun compte actif trouvé pour ce numéro de téléphone.', 404);
        }

        return $this->successResponse(
            CompteResource::collection($comptes),
            'Comptes récupérés avec succès'
        );
    }

    public function destroy(string $id)
    {
        $user = Auth::guard('api')->user();

        // Vérifier les permissions (seulement admin peut archiver)
        if ($user->type !== 'admin') {
            return $this->errorResponse('Seul un administrateur peut archiver un compte.', 403);
        }

        $compte = Compte::findOrFail($id);

        // Vérifier que le compte appartient à un client (pas à l'admin lui-même)
        if ($compte->titulaire === $user->id) {
            return $this->errorResponse('Vous ne pouvez pas archiver votre propre compte.', 403);
        }

        // Vérifier que le compte appartient à un client (pas à l'admin lui-même)
        if ($compte->titulaire === $user->id) {
            return $this->errorResponse('Vous ne pouvez pas archiver votre propre compte.', 403);
        }

        // Vérifier que le compte est actif (seuls les comptes actifs peuvent être archivés)
        if ($compte->statut !== 'actif') {
            return $this->errorResponse('Seul un compte actif peut être archivé.', 400);
        }

        // Archiver le compte (soft delete)
        $compte->update(['statut' => 'fermé']);

        return $this->successResponse(
            message: 'Compte supprimé avec succès'
        );
    }


}