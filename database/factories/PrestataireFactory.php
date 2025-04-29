<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Prestataire>
 */
class PrestataireFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            'nom' => $this->faker->name(),
            'profession' => $this->faker->randomElement(['Plombier', 'Électricien', 'Maçon', 'Peintre', 'Jardinier', 'Menuisier']),
            'phone' => $this->faker->phoneNumber(),
            'adresse' => $this->faker->address(),
            'created_at' => now(),
            'updated_at' => now(),
            
        ];
    }
}
