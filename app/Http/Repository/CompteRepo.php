<?php

namespace App\Http\Repository;

use App\Http\Interfaces\RepositoriesInterfaces\CompteRepositoryInterface;
use App\Models\Compte;
use Illuminate\Pagination\LengthAwarePaginator;

class CompteRepo implements CompteRepositoryInterface
{
    protected $model;

    public function __construct(Compte $model)
    {
        $this->model = $model;
    }

    public function findAll()
    {
        return $this->model->all();
    }

    public function findById(string $id): ?Compte
    {
        return $this->model->find($id);
    }

    public function getAll(array $filters = [], string $sort = 'created_at', string $order = 'desc', int $limit = 10): LengthAwarePaginator
    {
        $query = $this->model->query();

        if (!empty($filters['titulaire'])) {
            $query->where('titulaire', $filters['titulaire']);
        }


        if (!empty($filters['statut'])) {
            $query->withoutGlobalScopes();
            if ($filters['statut'] === 'archive') {
                $query->where('statut', 'supprimé');
            } else {
                $query->where('statut', $filters['statut']);
            }
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('numCompte', 'like', "%$search%")
                  ->orWhereHas('client.user', function ($q2) use ($search) {
                      $q2->where('prenom', 'like', "%$search%")
                         ->orWhere('nom', 'like', "%$search%");
                   });
            });
        }

        return $query->with('client.user')->orderBy($sort, $order)->paginate($limit);
    }

    public function create(array $data): Compte
    {
        return $this->model->create($data);
    }

    public function update(Compte $compte, array $data): Compte
    {
        $compte->update($data);
        return $compte->fresh();
    }

    public function delete(Compte $compte): bool
    {
        return $compte->delete();
    }
}

