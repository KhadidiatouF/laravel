<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\Compte;

class CompteSeeder extends Seeder
{
    public function run()
    {
        $clients = Client::all(); 

        if ($clients->isEmpty()) {
            $this->command->warn('Aucun client trouvé. Exécutez d\'abord ClientSeeder.');
            return;
        }

        $types = ['courant', 'epargne', 'cheque'];
        $statuts = ['actif', 'inactif', 'bloqué', 'fermé'];

        foreach ($clients as $client) {
            // Créer 1 à 3 comptes par client
            $nombreComptes = rand(1, 3);
            
            for ($i = 0; $i < $nombreComptes; $i++) {
                Compte::create([
                    'titulaire' => $client->id, // Maintenant c'est un UUID valide
                    'type' => $types[array_rand($types)],
                    'date_creation' => now()->subDays(rand(0, 365)),
                    'statut' => $statuts[array_rand($statuts)],
                ]);
            }
        }

        $this->command->info('Comptes créés : ' . Compte::count());
    }
}