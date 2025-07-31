<?php

namespace App\Filament\Admin\Resources\AgencyRoleResource\Pages;

use App\Filament\Admin\Resources\AgencyRoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAgencyRole extends CreateRecord
{
    protected static string $resource = AgencyRoleResource::class;

    // Auto-assigner l'agence et le créateur lors de la création
    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
    //     $data['agency_id'] = \Filament\Facades\Filament::getTenant()?->id;
    //     $data['created_by'] = auth()->user()->id;

    //     return $data;
    // }
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $allPermissions = [];

        foreach (\App\Services\AgencyPermissionService::getAllPermissions() as $group => $groupPermissions) {
            $groupKey = "permissions_group_{$group}";
            $groupValues = $data[$groupKey] ?? [];

            $allPermissions = array_merge($allPermissions, $groupValues);

            // Supprimer les groupes intermédiaires du tableau
            unset($data[$groupKey]);
        }

        $data['permissions'] = array_unique($allPermissions);

        return $data;
    }

}
