<?php

namespace App\Http\Services;

use App\Http\Interfaces\RepositoriesInterfaces\CompteRepositoryInterface;
use App\Models\Compte;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CompteService
{
    protected $compteRepo;
    protected $userService;
    protected $clientService;

    public function __construct(
        CompteRepositoryInterface $compteRepo,
        UserService $userService,
        ClientService $clientService
    ) {
        $this->compteRepo = $compteRepo;
        $this->userService = $userService;
        $this->clientService = $clientService;
    }

    public function getComptes()
    {
        return $this->compteRepo->findAll();
    }

    public function getCompteById(string $id): ?Compte
    {
        return $this->compteRepo->findById($id);
    }

    public function createCompte(array $data): Compte
    {
        $user = $this->userService->findOrCreate($data['client']);
        $client = $this->clientService->findOrCreate(['user_id' => $user->id]);

        return $this->compteRepo->create([
            'titulaire' => $client->id,
            'date_creation' => now()->toDateString()
        ]);
    }

    public function listComptes(array $filters, string $sort = 'created_at', string $order = 'desc', int $limit = 10): LengthAwarePaginator
    {
        return $this->compteRepo->getAll($filters, $sort, $order, $limit);
    }

    public function updateCompte(Compte $compte, array $data): Compte
    {
        return $this->compteRepo->update($compte, $data);
    }

    public function deleteCompte(Compte $compte): bool
    {
        return $this->compteRepo->delete($compte);
    }

    /**
     * Vérifier l'accès d'un utilisateur à un compte
     */
    public function checkAccountAccess(Compte $compte, $user): bool
    {
        return $compte->titulaire === $user->id;
    }
}