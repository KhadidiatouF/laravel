<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin>
 */
class AdminFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'nom' => strtoupper(fake()->lastName()),
            'prenom' => fake()->firstName(),
            'telephone' => fake()->unique()->numerify('77#######'),
            'email' => fake()->unique()->safeEmail(),
            'adresse' => fake()->city(),
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'type' => 'admin',
            'code_verification' => null,
        ];
    }
}
