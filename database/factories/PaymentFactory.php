<?php

namespace Database\Factories;

use App\Models\Flat;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'flat_id' => Flat::factory(), // Génère un appartement fictif
            'numero' => $this->faker->unique()->numerify('PAY-#####'),
            'tenant_id' => Tenant::factory(), // Génère un locataire fictif
            'current_month' => $this->faker->monthName(),
            'amount' => $this->faker->randomFloat(0, 50000, 500000), // Montant entre 50 000 et 500 000
            'status' => $this->faker->boolean(),
            'date_payment' => today(),
            'payment_method' => $this->faker->randomElement(['OM', 'Wave', 'Free Money', 'Chèque', 'Virement', 'Espèces']),
            'created_at' => now(),
        ];
    }
}
