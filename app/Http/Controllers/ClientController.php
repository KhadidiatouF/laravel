<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Http\Resources\ClientResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    use ApiResponseTrait;

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