<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // Pré-remplir les rôles d'agence lors du chargement
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = $this->record;
        $tenant = \Filament\Facades\Filament::getTenant();

        if ($tenant && $user) {
            // Récupérer les rôles d'agence actuels de l'utilisateur pour cette agence
            $agencyRoles = $user->agencyRoles()
                ->where('agency_roles.agency_id', $tenant->id)
                ->pluck('agency_roles.id')
                ->toArray();

            $data['agency_roles'] = $agencyRoles;
        }

        return $data;
    }

    // Sauvegarder les rôles d'agence lors de la mise à jour
    protected function afterSave(): void
    {
        $user = $this->record;
        $tenant = \Filament\Facades\Filament::getTenant();

        if ($tenant && $user) {
            // Synchroniser les rôles d'agence
            $agencyRoles = $this->data['agency_roles'] ?? [];

            // Récupérer les IDs des rôles d'agence de cette agence
            $currentAgencyRoleIds = \App\Models\AgencyRole::where('agency_id', $tenant->id)
                ->pluck('id')
                ->toArray();

            // Supprimer les anciens rôles d'agence pour cette agence
            $user->agencyRoles()->whereIn('agency_role_id', $currentAgencyRoleIds)->detach();

            // Assigner les nouveaux rôles d'agence
            if (!empty($agencyRoles)) {
                foreach ($agencyRoles as $roleId) {
                    $agencyRole = \App\Models\AgencyRole::find($roleId);
                    if ($agencyRole && $agencyRole->agency_id === $tenant->id) {
                        $user->assignAgencyRole($agencyRole, auth()->user());
                    }
                }
            }
        }
    }
}
