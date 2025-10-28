<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::factory()->create([
            'nom' => 'Admin',
            'prenom' => 'System',
            'email' => 'admin@example.com',
            'telephone' => '771234567',
            'adresse' => 'Dakar',
            'password' => bcrypt('password'),
            'type' => 'admin',
        ]);
    }
}
