<?php

namespace Database\Factories;

use App\Models\CategorieDepense;
use App\Models\Flat;
use App\Models\Prestataire;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        
            //
            return [
                'property_id' => Property::factory(), // Génère une propriété fictive
                'flat_id' => Flat::factory(), // Génère un appartement fictif
                'prestataire_id' => Prestataire::factory(), // Génère un prestataire fictif
                'categorie_depense_id' => CategorieDepense::factory(), // Génère une catégorie de dépense fictive
                'titre' => $this->faker->sentence(3),
                'type' => $this->faker->word(),
                'libelle' => $this->faker->paragraph(),
                'amount' => $this->faker->randomFloat(0, 1000, 100000), // Montant entre 1 000 et 100 000
                'payment_date' => $this->faker->date(),
                'payment_method' => $this->faker->randomElement(['OM', 'Wave', 'Free Money', 'Chèque', 'Virement', 'Espèces']),
               
            ];
        
    }
}
