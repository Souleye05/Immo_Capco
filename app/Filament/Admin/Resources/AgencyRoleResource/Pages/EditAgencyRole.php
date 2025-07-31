<?php

namespace App\Filament\Admin\Resources\AgencyRoleResource\Pages;

use App\Filament\Admin\Resources\AgencyRoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAgencyRole extends EditRecord
{
    protected static string $resource = AgencyRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
