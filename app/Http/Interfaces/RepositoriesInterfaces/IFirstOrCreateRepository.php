<?php
// app/Repositories/CompteRepositoryInterface.php

namespace App\Http\Interfaces\RepositoriesInterfaces;

use App\Models\User;
use App\Models\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IFirstOrCreateRepository
{
    public function findOrCreate(array $data): Client | User ;
}
