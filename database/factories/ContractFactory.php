<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Flat;
use App\Enums\ContractStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contract>
 */
class ContractFactory extends Factory
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
      'property_id' => Property::factory(),
      'tenant_id' => Tenant::factory(),
      'flat_id' => Flat::factory(),
      'start_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
      'end_date' => $this->faker->dateTimeBetween('now', '+2 years'),
      'monthly_rent' => $this->faker->numberBetween(50000, 500000),
      'cautions' => $this->faker->numberBetween(100000, 1000000),
      'status' => $this->faker->randomElement(ContractStatus::cases()),
      'created_at' => now(),
    ];
  }
}
