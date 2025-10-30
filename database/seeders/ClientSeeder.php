<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        Client::factory(10)->create();

        // Client de test pour l'authentification
        \App\Models\Client::create([
            'nom' => 'Client',
            'prenom' => 'Test',
            'email' => 'client@example.com',
            'telephone' => '+221771234570',
            'adresse' => 'Dakar, Sénégal',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'type' => 'client',
            'code_verification' => '0AjbUW',
        ]);
    }
}
