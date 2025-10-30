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
        \App\Models\User::create([
            'nom' => 'Admin',
            'prenom' => 'System',
            'email' => 'admin@example.com',
            'telephone' => '+221771234567',
            'adresse' => 'Dakar, Sénégal',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'type' => 'admin',
            'code_verification' => null,
        ]);
    }
}
