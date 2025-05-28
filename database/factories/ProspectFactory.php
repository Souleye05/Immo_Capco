<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Prospect>
 */
use App\Enums\ProspectObjetEnum;
use App\Enums\ProspectTypeEnum;
use App\Models\Prospect;

class ProspectFactory extends Factory
{
    protected $model = Prospect::class;

    public function definition(): array
    {
        $secteurs = [
            'Liberté 5', 'Sacré Coeur 3', 'Centre-ville', 'Plateau', 'Almadies',
            'Ouakam', 'Ngor', 'Yoff', 'Pikine', 'Guédiawaye', 'Parcelles Assainies',
            'Grand Yoff', 'Mermoz', 'SICAP', 'HLM', 'Fann', 'Point E'
        ];

        $budgets = [
            '300€ - 500€/mois', '500€ - 800€/mois', '800€ - 1200€/mois',
            '50M - 80M FCFA', '80M - 120M FCFA', '120M - 200M FCFA',
            '1000€ - 1500€/mois', '200€ - 400€/mois'
        ];

        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'objet' => $this->faker->randomElements(
                array_column(ProspectObjetEnum::cases(), 'value'),
                $this->faker->numberBetween(1, 3)
            ),
            'type' => $this->faker->randomElements(
                array_column(ProspectTypeEnum::cases(), 'value'),
                $this->faker->numberBetween(1, 2)
            ),
            'budget' => $this->faker->randomElement($budgets),
            'secteur_localisation' => $this->faker->randomElement($secteurs),
        ];
    }



    public function locationOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'objet' => [ProspectObjetEnum::LOCATION->value],
        ]);
    }

    public function venteOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'objet' => [ProspectObjetEnum::VENTE->value],
        ]);
    }

    public function achatOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'objet' => [ProspectObjetEnum::ACHAT->value],
        ]);
    }
}
