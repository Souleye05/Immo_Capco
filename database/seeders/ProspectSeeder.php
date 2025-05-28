<?php
namespace Database\Seeders;

use App\Models\Prospect;
use Illuminate\Database\Seeder;

class ProspectSeeder extends Seeder
{
    public function run(): void
    {
        // Prospects intéressés
        Prospect::factory()
            // ->interested()
            ->count(50)
            ->create();

        // Prospects pas intéressés
        Prospect::factory()
            // ->notInterested()
            ->count(20)
            ->create();

        // Prospects spécialisés location
        Prospect::factory()
            ->locationOnly()
            // ->interested()
            ->count(15)
            ->create();

        // Prospects spécialisés vente
        Prospect::factory()
            ->venteOnly()
            // ->interested()
            ->count(15)
            ->create();

        // Prospects spécialisés achat
        Prospect::factory()
            ->achatOnly()
            // ->interested()
            ->count(10)
            ->create();

        // Quelques prospects avec des données spécifiques
        Prospect::factory()->create([
            'name' => 'Jean Dupont',
            'email' => 'jean.dupont@example.com',
            'phone' => '+221 77 123 45 67',
            // 'interested' => true,
            'objet' => ['location'],
            'type' => ['f3', 'f4'],
            'budget' => '500€ - 800€/mois',
            'secteur_localisation' => 'Liberté 5',
        ]);

        Prospect::factory()->create([
            'name' => 'Marie Martin',
            'email' => 'marie.martin@example.com',
            'phone' => '+221 77 987 65 43',
            // 'interested' => true,
            'objet' => ['achat'],
            'type' => ['f2', 'f3'],
            'budget' => '80M - 120M FCFA',
            'secteur_localisation' => 'Almadies',
        ]);
    }
}
