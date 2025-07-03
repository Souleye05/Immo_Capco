<?php

namespace App\Filament\Resources\OwnerResource\Pages;

use App\Filament\Resources\OwnerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOwners extends ListRecords
{
    protected static string $resource = OwnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouveau propriétaire')
                ->icon('heroicon-o-plus')
                ,
        ];
    }
    public function getTitle(): string
    {
        return 'Gestion des Propriétaires';
    }
}
