<?php

namespace Database\Factories;

use App\Models\Owner;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Remittance>
 */
class RemittanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => Owner::factory(), // Génère un propriétaire fictif
            'property_id' => Property::factory(), // Génère une propriété fictive
            'mode_remit' => $this->faker->randomElement(['OM', 'Wave', 'Free Money', 'Chèque', 'Virement', 'Espèces']),
            'amount' => $this->faker->randomFloat(0, 1000, 100000), // Montant entre 1 000 et 100 000
            'remittance_date' => now(),
            'created_at' => now(),
        ];
    }
}
