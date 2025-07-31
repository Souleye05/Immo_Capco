<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Enums\PropertyType;
use App\Enums\CommissionUnit;
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
            'agency_id' => Agency::factory(),
            'name' => $this->faker->company(),
            'address' => $this->faker->address(),
            'type' => $this->faker->randomElement(PropertyType::cases()),
            'number_flat' => $this->faker->numberBetween(1, 50),
            'commission_value' => $this->faker->randomFloat(2, 5, 25), // Pourcentage entre 5 et 25
            'commission_unit' => CommissionUnit::PERCENTAGE,
            'created_at' => now(),
        ];
    }
}
