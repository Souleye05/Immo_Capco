<?php

namespace Database\Factories;

use App\Models\CategorieDepense;
use Illuminate\Database\Eloquent\Factories\Factory;
use Faker\Factory as FakerFactory;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CategorieDepense>
 */
class CategorieDepenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    // CategorieDepenseFactory.php
public function definition(): array
{
    return [
        'categorie' => fake()->unique()->word(),
        'description' => $this->faker->sentence(),
    ];
}

   
    
}
