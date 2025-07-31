<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    // Auto-assigner l'agence et les rôles lors de la création
    protected function afterCreate(): void
    {
        $user = $this->record;
        $tenant = \Filament\Facades\Filament::getTenant();

        if ($tenant && $user) {
            // Associer l'utilisateur à l'agence courante
            $user->agencys()->syncWithoutDetaching([$tenant->id]);

            // Assigner les rôles d'agence sélectionnés
            $agencyRoles = $this->data['agency_roles'] ?? [];
            if (!empty($agencyRoles)) {
                foreach ($agencyRoles as $roleId) {
                    $agencyRole = \App\Models\AgencyRole::find($roleId);
                    if ($agencyRole) {
                        $user->assignAgencyRole($agencyRole, auth()->user());
                    }
                }
            }
        }
    }
}
