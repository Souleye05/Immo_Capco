<?php

namespace App\Filament\Admin\Resources\UnsoldResource\Pages;

use App\Filament\Admin\Resources\UnsoldResource;
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
                ,
        ];
    }

    public function getTitle(): string
    {
        return 'Gestion des impayés';
    }
}
