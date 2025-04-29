<?php

namespace Database\Factories;

use App\Models\Unsold;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VersementImp>
 */
class VersementImpFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unsold_id' => Unsold::factory(), // Génère un impayé fictif
            'reference' => $this->faker->unique()->bothify('VERSEMENTIMP-###'),
            'amount' => $this->faker->randomFloat(0, 1000, 100000), // Montant entre 1 000 et 100 000
            'versement_date' => today(),
            'created_at' => now(),
        ];
    }
}
