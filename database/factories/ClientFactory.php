<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'nom' => strtoupper(fake()->lastName()),
            'prenom' => fake()->firstName(),
            'telephone' => fake()->unique()->numerify('77#######'),
            'email' => fake()->unique()->safeEmail(),
            'adresse' => fake()->city(),
            'password' => Hash::make('password'),
            'type' => 'client',
        ];
    }
}
