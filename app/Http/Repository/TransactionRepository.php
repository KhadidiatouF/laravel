<?php

namespace App\Http\Repository;

use App\Http\Interfaces\RepositoriesInterfaces\TransactionRepositoryInterface;
use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TransactionRepository implements TransactionRepositoryInterface
{
    protected $model;

    public function __construct(Transaction $model)
    {
        $this->model = $model;
    }

    public function findAll(): Collection
    {
        return $this->model->all();
    }

    public function findById(string $id): ?Transaction
    {
        return $this->model->find($id);
    }

    public function getAll(array $filters = [], string $sort = 'date_transaction', string $order = 'desc', int $limit = 10): LengthAwarePaginator
    {
        $query = $this->model->with(['compte.client', 'compteDestination.client']);

        // Appliquer les filtres
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        if (!empty($filters['compte_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('compte_id', $filters['compte_id'])
                  ->orWhere('compte_destination_id', $filters['compte_id']);
            });
        }

        if (!empty($filters['user_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereHas('compte', function ($compteQuery) use ($filters) {
                    $compteQuery->where('titulaire', $filters['user_id']);
                })->orWhereHas('compteDestination', function ($compteDestQuery) use ($filters) {
                    $compteDestQuery->where('titulaire', $filters['user_id']);
                });
            });
        }

        // Filtre par période
        if (!empty($filters['date_debut']) && !empty($filters['date_fin'])) {
            $query->whereBetween('date_transaction', [
                $filters['date_debut'] . ' 00:00:00',
                $filters['date_fin'] . ' 23:59:59'
            ]);
        } elseif (!empty($filters['date_debut'])) {
            $query->where('date_transaction', '>=', $filters['date_debut'] . ' 00:00:00');
        } elseif (!empty($filters['date_fin'])) {
            $query->where('date_transaction', '<=', $filters['date_fin'] . ' 23:59:59');
        }

        return $query->orderBy($sort, $order)->paginate($limit);
    }

    public function create(array $data): Transaction
    {
        return $this->model->create($data);
    }

    public function update(Transaction $transaction, array $data): Transaction
    {
        $transaction->update($data);
        return $transaction->fresh();
    }

    public function delete(Transaction $transaction): bool
    {
        return $transaction->delete();
    }

    public function getTransactionsByCompte(string $compteId, array $filters = []): Collection
    {
        $query = $this->model->where(function ($q) use ($compteId) {
            $q->where('compte_id', $compteId)
              ->orWhere('compte_destination_id', $compteId);
        })->where('statut', 'validee');

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->with(['compte.client', 'compteDestination.client'])
                    ->orderBy('date_transaction', 'desc')
                    ->get();
    }

    public function getBalanceByCompte(string $compteId): float
    {
        $result = $this->model->where(function ($q) use ($compteId) {
            $q->where('compte_id', $compteId)
              ->orWhere('compte_destination_id', $compteId);
        })->where('statut', 'validee')
          ->selectRaw('
              SUM(CASE
                  WHEN compte_id = ? THEN
                      CASE WHEN type IN (\'depot\') THEN montant ELSE -montant END
                  WHEN compte_destination_id = ? THEN
                      CASE WHEN type IN (\'transfert\', \'payement\') THEN montant ELSE 0 END
                  ELSE 0
              END) as solde
          ', [$compteId, $compteId])
          ->value('solde');

        return (float) ($result ?? 0);
    }

    public function getStatistics(): array
    {
        $totalTransactions = $this->model->where('statut', 'validee')->count();

        $balanceGenerale = DB::table('transactions')
            ->where('statut', 'validee')
            ->selectRaw('
                SUM(CASE WHEN type IN (\'depot\') THEN montant ELSE 0 END) -
                SUM(CASE WHEN type IN (\'retrait\', \'transfert\', \'payement\') THEN montant ELSE 0 END)
                as balance
            ')
            ->value('balance') ?? 0;

        return [
            'totalTransactions' => $totalTransactions,
            'balanceGenerale' => (float) $balanceGenerale,
        ];
    }
}