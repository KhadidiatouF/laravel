<?php

namespace App\Http\Repository;

use App\Models\Client;
use App\Http\Interfaces\RepositoriesInterfaces\IFirstOrCreateRepository;

class ClientRepository implements IFirstOrCreateRepository
{
    protected $model;

    public function __construct(Client $client)
    {
        $this->model = $client;
    }

    /**
     * Créer un nouveau client
     *
     * @param array $data
     * @return client
     */
    public function findOrCreate(array $data): Client
    {
        return $this->model->firstOrCreate(
                ['user_id' => $data['user_id']],
                ['user_id' => $data['user_id']], 
        );
    }

        // public function findById(string $id): ?client
        // {

        // }

        // public function update(client $client, array $data): client
        // {

        // }  
        // public function delete(client $client): client
        // {

        // }
}