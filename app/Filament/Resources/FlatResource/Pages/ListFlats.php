<?php

namespace App\Filament\Resources\FlatResource\Pages;

use App\Filament\Resources\FlatResource;
use App\Filament\Resources\FlatResource\Widgets\FlatStatsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFlats extends ListRecords
{
    protected static string $resource = FlatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Ajouter Appartement')
                ->icon('heroicon-o-plus')
                ,
        ];
    }

    public  function getHeaderWidgets(): array
    {
        return [
            FlatStatsWidget::class,
        ];
    }

    public function getTitle(): string
    {
        return 'Gestion des Appartements';
    }
}
