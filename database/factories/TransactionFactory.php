<?php
namespace Database\Factories;

use App\Models\Compte;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(['depot', 'retrait', 'transfert', 'virement']);
        $compteSource = Compte::inRandomOrder()->first() ?? Compte::factory()->create();

        // Pour les transferts, créer ou récupérer un compte destination différent
        $compteDestination = null;
        if (in_array($type, ['transfert', 'virement'])) {
            $compteDestination = Compte::where('id', '!=', $compteSource->id)->inRandomOrder()->first();
            if (!$compteDestination) {
                $compteDestination = Compte::factory()->create();
            }
        }

        return [
            'compte_id' => $compteSource->id,
            'compte_destination_id' => $compteDestination?->id,
            'type' => $type,
            'montant' => fake()->randomFloat(2, 1000, 500000),
            'description' => fake()->optional(0.7)->sentence(),
            'numero_transaction' => 'TXN-' . fake()->unique()->numberBetween(100000, 999999),
            'statut' => fake()->randomElement(['en_cours', 'validee', 'rejete']),
            'date_transaction' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }

    // États pour différents types de transactions
    public function depot()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'depot',
                'compte_destination_id' => null,
                'statut' => 'validee',
            ];
        });
    }

    public function retrait()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'retrait',
                'compte_destination_id' => null,
                'statut' => 'validee',
            ];
        });
    }

    public function transfert()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'transfert',
                'statut' => 'validee',
            ];
        });
    }

    public function validee()
    {
        return $this->state(function (array $attributes) {
            return [
                'statut' => 'validee',
            ];
        });
    }

    public function rejetee()
    {
        return $this->state(function (array $attributes) {
            return [
                'statut' => 'rejete',
            ];
        });
    }
}
