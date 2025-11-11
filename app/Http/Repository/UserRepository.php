<?php

namespace App\Http\Repository;

use App\Models\User;
use App\Models\Client;
use App\Http\Interfaces\RepositoriesInterfaces\IFirstOrCreateRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserRepository implements IFirstOrCreateRepository
{
    protected $model;

    public function __construct(User $user)
    {
        $this->model = $user;
    }


    /**
     * Créer un nouveau compte
     *
     * @param array $data
     * @return User
     */
    public function findOrCreate(array $data): User
    {
        $plainPassword = $data['password'] ?? $this->generateRandomPassword();
        $code = $this->generateCode();

        $user = $this->model->firstOrCreate(
                ['nci' => $data['nci']],
                [
                    'prenom' => $data['prenom'],
                    'nom' => $data['nom'],
                    'adresse' => $data['adresse'],
                    'telephone' => $data['telephone'],
                    'email' => $data['email'],
                    'statut' => 'actif',
                    'password' => Hash::make($plainPassword),
                    'code' => $code,
                ]
        );

        // Stocker le mot de passe en clair temporairement pour l'email
        $user->plain_password = $plainPassword;

        return $user;
    }

    private function generateRandomPassword(): string
    {
        return Str::random(8); // Génère un mot de passe aléatoire de 8 caractères
    }

    private function generateCode(): string
    {
        return strtoupper(Str::random(6)); // Génère un code aléatoire de 6 caractères
    }
}