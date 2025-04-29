<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Versement>
 */
class VersementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(), // Génère un paiement fictif
            'reference' => $this->faker->unique()->bothify('VERSEMENT-###'),
            'amount' => $this->faker->randomFloat(0, 1000, 100000), // Montant entre 1 000 et 100 000
            'versement_date' => today(),
            'created_at' => now(),
        ];
    }
}
