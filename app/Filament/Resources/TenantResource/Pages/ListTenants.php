<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use App\Models\Tenant;
use Filament\Actions;
use Filament\Forms\Components\Builder;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\IconPosition;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class ListTenants extends ListRecords
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Ajouter locataire')
                ->icon('heroicon-o-plus')
                ->color('info'),
        ];
    }

    public function getTitle(): string
    {
        return 'Gestion des Locataires';
    }

    /**
     * Retourne les onglets pour la liste des locataires
     * 
     * @return array
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tous')
                ->label('Tous les locataires')
                ->badge(Tenant::count())
                ->icon('heroicon-o-users'),

            'with_payments' => Tab::make('Avec paiements')
                ->label('Avec paiements')
                ->badge(Tenant::has('payment')->count())
                ->icon('heroicon-o-credit-card')
                ->modifyQueryUsing(fn (EloquentBuilder $query) => $query->has('payment')),

            'without_payments' => Tab::make('Sans paiements')
                ->label('Sans paiements')
                ->badge(Tenant::doesntHave('payment')->count())
                ->icon('heroicon-o-exclamation-triangle')
                ->modifyQueryUsing(fn (EloquentBuilder $query) => $query->doesntHave('payment')),

            'with_flats' => Tab::make('Avec appartements')
                ->label('Avec appartements')
                ->badge(Tenant::has('flatThroughContract')->count())
                ->icon('heroicon-o-home')
                ->modifyQueryUsing(fn (EloquentBuilder $query) => $query->has('flatThroughContract')),

            'without_flats' => Tab::make('Sans appartements')
                ->label('Sans appartements')
                ->badge(Tenant::doesntHave('flatThroughContract')->count())
                ->icon('heroicon-o-home-modern')
                ->modifyQueryUsing(fn (EloquentBuilder $query) => $query->doesntHave('flatThroughContract')),

        ];
    }
}