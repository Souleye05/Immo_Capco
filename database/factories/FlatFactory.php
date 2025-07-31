<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Flat>
 */
class FlatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'property_commission_value' => $this->faker->randomFloat(0, 1000, 100000),
            'property_commission_unit' => $this->faker->randomElement(['%', 'F CFA']),
            'reference' => $this->faker->unique()->bothify('FLAT-###'),
            'designation' => $this->faker->word(),
            'level' => $this->faker->randomElement(['RDC', '1er', '2ème', '3ème']),
            'type' => $this->faker->randomElement(['studio', 'f1', 'f2', 'f3', 'f4', 'f5', 'f6+']),
            'loyer' => $this->faker->randomFloat(0, 50000, 500000),
            'caution' => $this->faker->randomFloat(0, 100000, 1000000),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
