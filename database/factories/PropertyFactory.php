<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Property>
 */
class PropertyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'address' => $this->faker->address(),
            'type' => $this->faker->randomElement(['Immeuble', 'Villa', 'Commerce']),
            'number_flat' => $this->faker->numberBetween(1, 50),
            'commission_value' => $this->faker->randomFloat(0, 1000, 100000), // Montant entre 1 000 et 100 000
            'commission_unit' => $this->faker->randomElement(['%', 'F CFA']),
            'created_at' => now(),
        ];
    }
}
