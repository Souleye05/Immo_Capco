<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTenants extends ListRecords
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Ajouter locataire')
                ->icon('heroicon-o-plus')
                ->color('info'),
        ];
    }

    public function getTitle(): string
    {
        return 'Gestion des Locataires';
    }
}
