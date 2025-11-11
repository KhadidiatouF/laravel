<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Compte;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponseTrait;
    public function index(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return $this->errorResponse('Utilisateur non authentifié.', 401);
        }

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

    public function store(Request $request)
    {
        $currentUser = Auth::guard('api')->user();

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

    public function show($id)
    {
        $currentUser = Auth::guard('api')->user();

        // Vérifier les permissions (admin ou utilisateur propriétaire)
        if ($currentUser->type === 'client' && $currentUser->id !== $id) {
            return $this->errorResponse('Accès refusé à cet utilisateur.', 403);
        }

        $user = User::findOrFail($id);
        return $this->successResponse($user);
    }

    public function update(Request $request, $id)
    {
        $currentUser = Auth::guard('api')->user();

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

    public function destroy($id)
    {
        $currentUser = Auth::guard('api')->user();

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