<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Contract;
use App\Models\Flat;
use App\Models\Tenant;
use App\Enums\PaymentMethod;
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
            'agency_id' => Agency::factory(),
            'contract_id' => Contract::factory(),
            'flat_id' => Flat::factory(),
            'numero' => $this->faker->unique()->numerify('PAY-#####'),
            'tenant_id' => Tenant::factory(),
            'current_month' => $this->faker->monthName(),
            'amount' => $this->faker->randomFloat(0, 50000, 500000),
            'status' => $this->faker->boolean(),
            'date_payment' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'type' => $this->faker->randomElement(\App\Enums\PaymentType::cases()),
            'created_at' => now(),
        ];
    }
}
