<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\Compte;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        // Vérifier qu'il y a des comptes avant de créer des transactions
        if (Compte::count() === 0) {
            $this->command->info('Aucun compte trouvé. Création de comptes de test...');
            $this->call([
                CompteSeeder::class,
            ]);
        }

        $this->command->info('Création des transactions...');

        // Créer différents types de transactions
        Transaction::factory(5)->depot()->create();
        Transaction::factory(5)->retrait()->create();
        Transaction::factory(0)->transfert()->create();
        Transaction::factory(5)->payement()->create();
        Transaction::factory(3)->rejetee()->create();

        $this->command->info('Transactions créées avec succès !');
    }
}
