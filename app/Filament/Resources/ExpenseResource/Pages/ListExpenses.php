<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use App\Filament\Resources\ExpenseResource\Widgets\ExpenseStatsOverview;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExpenses extends ListRecords
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouvelle Dépense')
                ->icon('heroicon-o-plus')
                ->color('info'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ExpenseStatsOverview::class,
        ];
    }

    public function getTitle(): string
    {
        return 'Gestion des Dépenses';
    }
}
