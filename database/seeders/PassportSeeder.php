<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PassportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un client d'accès personnel
        $clientId = DB::table('oauth_clients')->insertGetId([
            'user_id' => null,
            'name' => 'Laravel Personal Access Client',
            'secret' => Str::random(40),
            'provider' => null,
            'redirect' => 'http://localhost',
            'personal_access_client' => true,
            'password_client' => false,
            'revoked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Créer l'entrée dans oauth_personal_access_clients
        DB::table('oauth_personal_access_clients')->insert([
            'client_id' => $clientId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Afficher le client ID et secret pour référence
        $client = DB::table('oauth_clients')->find($clientId);
        echo "Personal Access Client créé:\n";
        echo "Client ID: {$client->id}\n";
        echo "Client Secret: {$client->secret}\n";
    }
}