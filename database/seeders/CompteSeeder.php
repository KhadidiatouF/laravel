<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Client;
use Illuminate\Support\Facades\DB;

class CompteSeeder extends Seeder
{
    public function run()
    {
        $clients = Client::all(); 

        foreach ($clients as $client) {
            DB::table('comptes')->insert([
                'titulaire' => $client->id,
                'type' => 'courant',
                'date_creation' => now(),
                'statut' => 'actif',
                'id' => Str::uuid(),    
                'numCompte' => 'C-'.now()->format('Ymd').'-'.Str::random(4),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
