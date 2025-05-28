<?php

namespace App\Filament\Resources\PrestataireResource\Pages;

use App\Filament\Resources\PrestataireResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPrestataires extends ListRecords
{
    protected static string $resource = PrestataireResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouveau prestataire')
                ->icon('heroicon-o-plus')
                ->color('info'),
        ];
    }
    public function getTitle(): string
    {
        return 'Gestion des Prestataires';
    }
}
