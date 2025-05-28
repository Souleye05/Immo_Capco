<?php

namespace App\Filament\Resources\ProspectResource\Pages;

use App\Filament\Resources\ProspectResource;
use App\Filament\Resources\ProspectResource\Widgets\ProspectStatsWidget;
use App\Models\Prospect;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Filament\Support\Enums\IconPosition;
use Illuminate\Database\Eloquent\Builder;

class ListProspects extends ListRecords
{
    protected static string $resource = ProspectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouveau prospect')
                ->icon('heroicon-o-plus')
                ->color('info'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tous')
                ->badge(Prospect::count())
                ->icon('heroicon-o-users'),

            'location' => Tab::make('Location')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereObjet(['location']))
                ->badge(Prospect::whereObjet(['location'])->count())
                ->icon('heroicon-o-key')
                ->iconPosition(IconPosition::Before),

            'vente' => Tab::make('Vente')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereObjet(['vente']))
                ->badge(Prospect::whereObjet(['vente'])->count())
                ->icon('heroicon-o-home')
                ->iconPosition(IconPosition::Before),

            'achat' => Tab::make('Achat')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereObjet(['achat']))
                ->badge(Prospect::whereObjet(['achat'])->count())
                ->icon('heroicon-o-shopping-cart')
                ->iconPosition(IconPosition::Before),

            'gerance' => Tab::make('Gérance')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereObjet(['gerance']))
                ->badge(Prospect::whereObjet(['gerance'])->count())
                ->icon('heroicon-o-building-office-2')
                ->iconPosition(IconPosition::Before),

            'recent' => Tab::make('Récents')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('created_at', '>=', now()->subWeek()))
                ->badge(Prospect::where('created_at', '>=', now()->subWeek())->count())
                ->icon('heroicon-o-clock')
                ->iconPosition(IconPosition::Before),

            'with_budget' => Tab::make('Avec Budget')
                ->modifyQueryUsing(fn(Builder $query) => $query->withBudget())
                ->badge(Prospect::withBudget()->count())
                ->icon('heroicon-o-currency-euro')
                ->iconPosition(IconPosition::Before),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ProspectStatsWidget::class,
        ];
    }
    public function getTitle(): string
    {
        return 'Gestion des Prospects';
    }

    public function getSubheading(): string
    {
        return 'Gérez vos prospects immobiliers et leurs préférences';
    }
}
