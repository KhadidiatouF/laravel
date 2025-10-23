<?php
namespace Database\Factories;

use App\Models\Compte;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'compte_id' => Compte::factory(), 
            'type' => fake()->randomElement(['depot', 'retrait']),
            'montant' => fake()->randomFloat(2, 1000, 500000),
            'date_transaction' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
