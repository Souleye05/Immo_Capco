<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Unsold>
 */
class UnsoldFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(), // Génère un locataire fictif
            'reference' => $this->faker->unique()->bothify('UNSOLD-###'),
            'amount' => $this->faker->randomFloat(0, 1000, 100000), // Montant entre 1 000 et 100 000
            'motif' => $this->faker->sentence(),
            'etat' => $this->faker->boolean(),
            // date now
            'date' => now(),
            'created_at' => now(),
            'updated_at' => now(),

            // 'date' => $this->faker->date(),
        ];
    }
}
