<?php

namespace App\Filament\Resources\FlatResource\Widgets;

use App\Models\Flat;
use App\Services\PaymentService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FlatStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $paymentService = app(PaymentService::class);

        // Calculs des statistiques
        $totalFlats = Flat::count(); // Nombre total d'appartements
        $totalRevenue = Flat::sum('loyer'); // Revenu total des loyers
        $totalCommissions = $paymentService->calculateTotalCommissionsFromRents(); // Montant total des commissions des loyers

        return [
            Stat::make('Nombre total d\'appartements', $totalFlats)
                ->description('Appartements enregistrés')
                ->icon('heroicon-o-home-modern')
                ->color('primary'),

            Stat::make('Montant total des commissions', number_format($totalCommissions, 0, ',', ' ') . ' F CFA')
                ->description('Somme des commissions des loyers')
                ->icon('heroicon-o-currency-dollar')
                ->color('success'),

            Stat::make('Revenu total des loyers', number_format($totalRevenue, 0, ',', ' ') . ' F CFA')
                ->description('Revenu total généré par les loyers')
                ->icon('heroicon-o-banknotes')
                ->color('info'),
        ];
    }
}
