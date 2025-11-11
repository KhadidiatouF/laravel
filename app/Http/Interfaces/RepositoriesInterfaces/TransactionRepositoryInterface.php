<?php

namespace App\Http\Interfaces\RepositoriesInterfaces;

use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TransactionRepositoryInterface
{
    public function findAll(): Collection;

    public function findById(string $id): ?Transaction;

    public function getAll(array $filters = [], string $sort = 'date_transaction', string $order = 'desc', int $limit = 10): LengthAwarePaginator;

    public function create(array $data): Transaction;

    public function update(Transaction $transaction, array $data): Transaction;

    public function delete(Transaction $transaction): bool;

    public function getTransactionsByCompte(string $compteId, array $filters = []): Collection;

    public function getBalanceByCompte(string $compteId): float;

    public function getStatistics(): array;
}