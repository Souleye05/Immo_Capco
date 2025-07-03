<?php

namespace App\Filament\Resources\VersementResource\Pages;

use App\Filament\Resources\VersementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVersements extends ListRecords
{
    protected static string $resource = VersementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Gestion des Versements';
    }

    public function getSubheading(): string
    {
        return 'Liste des versements effectués par les locataires ';
    }
}
