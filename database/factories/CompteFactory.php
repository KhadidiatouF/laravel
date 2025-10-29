<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'titulaire' => Client::factory(), // crée un client associé
            'type' => fake()->randomElement(['courant', 'epargne', 'cheque']),
            'date_creation' => now(),
            'statut' => fake()->randomElement(['actif', 'inactif']),
        ];
    }
}
