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
        Transaction::factory(20)->depot()->create();
        Transaction::factory(15)->retrait()->create();
        Transaction::factory(10)->transfert()->create();
        Transaction::factory(5)->validee()->create();
        Transaction::factory(3)->rejetee()->create();

        $this->command->info('Transactions créées avec succès !');
    }
}
