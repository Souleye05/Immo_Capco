<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\AgencyRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgencyRole>
 */
class AgencyRoleFactory extends Factory
{
  /**
   * The name of the factory's corresponding model.
   *
   * @var string
   */
  protected $model = AgencyRole::class;

  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    $name = $this->faker->randomElement([
      'Manager',
      'Assistant Manager',
      'Sales Agent',
      'Property Manager',
      'Administrative Assistant',
      'Marketing Coordinator',
      'Finance Officer',
      'Customer Service Representative'
    ]);

    return [
      'agency_id' => Agency::factory(),
      'name' => $name,
      'slug' => Str::slug($name),
      'description' => $this->faker->sentence(),
      'permissions' => $this->faker->randomElements([
        'view_properties',
        'create_properties',
        'edit_properties',
        'delete_properties',
        'view_contracts',
        'create_contracts',
        'edit_contracts',
        'delete_contracts',
        'view_payments',
        'create_payments',
        'edit_payments',
        'delete_payments',
        'view_reports',
        'manage_users',
        'manage_settings'
      ], $this->faker->numberBetween(2, 8)),
      'is_active' => $this->faker->boolean(90), // 90% chance of being active
      'created_by' => User::factory(),
    ];
  }

  /**
   * Indicate that the agency role is active.
   */
  public function active(): static
  {
    return $this->state(fn(array $attributes) => [
      'is_active' => true,
    ]);
  }

  /**
   * Indicate that the agency role is inactive.
   */
  public function inactive(): static
  {
    return $this->state(fn(array $attributes) => [
      'is_active' => false,
    ]);
  }

  /**
   * Create a manager role with full permissions.
   */
  public function manager(): static
  {
    return $this->state(fn(array $attributes) => [
      'name' => 'Manager',
      'slug' => 'manager',
      'description' => 'Full access manager role',
      'permissions' => [
        'view_properties',
        'create_properties',
        'edit_properties',
        'delete_properties',
        'view_contracts',
        'create_contracts',
        'edit_contracts',
        'delete_contracts',
        'view_payments',
        'create_payments',
        'edit_payments',
        'delete_payments',
        'view_reports',
        'manage_users',
        'manage_settings'
      ],
      'is_active' => true,
    ]);
  }

  /**
   * Create a basic agent role with limited permissions.
   */
  public function agent(): static
  {
    return $this->state(fn(array $attributes) => [
      'name' => 'Agent',
      'slug' => 'agent',
      'description' => 'Basic agent role with limited permissions',
      'permissions' => [
        'view_properties',
        'view_contracts',
        'view_payments',
      ],
      'is_active' => true,
    ]);
  }

  /**
   * Create a role with specific permissions.
   */
  public function withPermissions(array $permissions): static
  {
    return $this->state(fn(array $attributes) => [
      'permissions' => $permissions,
    ]);
  }

  /**
   * Create a role for a specific agency.
   */
  public function forAgency(Agency $agency): static
  {
    return $this->state(fn(array $attributes) => [
      'agency_id' => $agency->id,
    ]);
  }

  /**
   * Create a role created by a specific user.
   */
  public function createdBy(User $user): static
  {
    return $this->state(fn(array $attributes) => [
      'created_by' => $user->id,
    ]);
  }
}
