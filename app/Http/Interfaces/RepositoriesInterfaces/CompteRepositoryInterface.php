<?php

namespace App\Http\Interfaces\RepositoriesInterfaces;

use App\Models\Compte;
use Illuminate\Pagination\LengthAwarePaginator;

interface CompteRepositoryInterface
{
    public function findAll();

    public function findById(string $id): ?Compte;

    public function getAll(array $filters = [], string $sort = 'created_at', string $order = 'desc', int $limit = 10): LengthAwarePaginator;

    public function create(array $data): Compte;

    public function update(Compte $compte, array $data): Compte;

    public function delete(Compte $compte): bool;
}

