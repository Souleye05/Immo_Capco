<?php

namespace App\Filament\Resources\RemittanceResource\Pages;

use App\Filament\Resources\PropertyResource\Widgets\PropertyPaymentStatsWidget;
use App\Filament\Resources\RemittanceResource;
use App\Filament\Resources\RemittanceResource\Widgets\ReversementStatsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRemittances extends ListRecords
{
    protected static string $resource = RemittanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouveau Reversement')
                ->icon('heroicon-o-plus')
                ,
            
        ];
    }

    public function getHeaderWidgets(): array
    {
        return [
            PropertyPaymentStatsWidget::class,
            ReversementStatsWidget::class,
        ];
    }

     public function getTitle(): string
    {
        return 'Gestion des Reversements';
    }

}
