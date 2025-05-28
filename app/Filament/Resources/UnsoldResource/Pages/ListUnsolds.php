<?php

namespace App\Filament\Resources\UnsoldResource\Pages;

use App\Filament\Resources\UnsoldResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUnsolds extends ListRecords
{
    protected static string $resource = UnsoldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Ajouter impayé')
                ->icon('heroicon-o-plus')
                ->color('danger'),
        ];
    }

    public function getTitle(): string
    {
        return 'Gestion des impayés';
    }
}
