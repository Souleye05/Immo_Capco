<?php

namespace App\Filament\Admin\Resources\PropertyResource\Pages;

use App\Filament\Admin\Resources\PropertyResource;
use App\Filament\Admin\Resources\PropertyResource\Widgets\PropertyPaymentStatsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProperties extends ListRecords
{
    protected static string $resource = PropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouvelle Immeuble')
                ->icon('heroicon-o-plus')
                ,
        ];
    }

    // use App\Filament\Admin\Resources\PropertyResource\Widgets\PropertyPaymentStatsWidget;

    protected function getHeaderWidgets(): array
    {
        return [
            // PropertyPaymentStatsWidget::class,
        ];
    }
    public function getTitle(): string
    {
        return 'Gestion des Immeubles';
    }
}
