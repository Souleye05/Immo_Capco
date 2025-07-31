<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AgencyRole;
use App\Models\Agency;
use App\Models\User;

class AgencyRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer les agences et leurs propriétaires
        $agencies = Agency::with('agencyOwners')->get();

        foreach ($agencies as $agency) {
            $agencyOwner = $agency->agencyOwners()->first();

            if (!$agencyOwner) {
                $this->command->warn("Aucun agency-owner trouvé pour l'agence: {$agency->name}");
                continue;
            }

            // Créer des rôles de démonstration pour chaque agence
            $roles = [
                [
                    'name' => 'Manager Commercial',
                    'slug' => 'manager-commercial',
                    'description' => 'Responsable de la prospection et de la négociation commerciale',
                    'permissions' => [
                        'view_properties',
                        'edit_properties',
                        'create_properties',
                        'view_contracts',
                        'edit_contracts',
                        'create_contracts',
                        'view_tenants',
                        'edit_tenants',
                        'create_tenants',
                        'view_owners',
                        'edit_owners',
                        'create_owners',
                        'view_prospects',
                        'edit_prospects',
                        'manage_unsolds',
                        'view_analytics'
                    ]
                ],
                [
                    'name' => 'Assistant Comptable',
                    'slug' => 'assistant-comptable',
                    'description' => 'Gestion des paiements et suivi financier',
                    'permissions' => [
                        'view_properties',
                        'view_contracts',
                        'view_tenants',
                        'view_payments',
                        'edit_payments',
                        'create_payments',
                        'view_expenses',
                        'edit_expenses',
                        'manage_remittances',
                        'view_financial_reports'
                    ]
                ],
                [
                    'name' => 'Gestionnaire Locatif',
                    'slug' => 'gestionnaire-locatif',
                    'description' => 'Gestion quotidienne des locations et relations locataires',
                    'permissions' => [
                        'view_properties',
                        'edit_properties',
                        'view_contracts',
                        'edit_contracts',
                        'view_tenants',
                        'edit_tenants',
                        'create_tenants',
                        'view_payments',
                        'edit_payments',
                        'view_expenses',
                        'edit_expenses'
                    ]
                ],
                [
                    'name' => 'Stagiaire',
                    'slug' => 'stagiaire',
                    'description' => 'Accès limité en lecture seule pour formation',
                    'permissions' => [
                        'view_properties',
                        'view_contracts',
                        'view_tenants',
                        'view_payments',
                        'view_prospects'
                    ]
                ]
            ];

            foreach ($roles as $roleData) {
                AgencyRole::firstOrCreate(
                    [
                        'agency_id' => $agency->id,
                        'slug' => $roleData['slug']
                    ],
                    [
                        'name' => $roleData['name'],
                        'description' => $roleData['description'],
                        'permissions' => $roleData['permissions'],
                        'is_active' => true,
                        'created_by' => $agencyOwner->id,
                    ]
                );
            }

            $this->command->info("Rôles créés pour l'agence: {$agency->name}");
        }
    }
}
