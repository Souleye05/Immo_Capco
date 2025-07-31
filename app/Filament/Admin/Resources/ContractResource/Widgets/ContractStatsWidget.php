<?php

namespace App\Filament\Admin\Resources\ContractResource\Widgets;

use App\Enums\ContractStatus;
use App\Models\Contract;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContractStatsWidget extends BaseWidget
{
    // protected function getStats(): array
    // {
    //     // Calcul du revenu total formaté
    //     $totalRevenus = Contract::where('status', 'active')
    //         ->with('payments')
    //         ->get()
    //         ->sum(fn($contract) => $contract->payments->where('status', 'paid')->sum('amount'));

    //     return [
    //         Stat::make('Contrats Actifs', Contract::where('status', 'active')->count())
    //             ->description('Contrats en cours')
    //             ->descriptionIcon('heroicon-m-check-circle')
    //             ->color('success'),

    //         Stat::make('Expire Bientôt', Contract::expiringSoon()->count())
    //             ->description('Dans les 90 prochains jours')
    //             ->descriptionIcon('heroicon-m-exclamation-triangle')
    //             ->color('warning'),

    //         Stat::make('Expirés', Contract::expired()->count())
    //             ->description('Nécessitent action')
    //             ->descriptionIcon('heroicon-m-x-circle')
    //             ->color('danger'),

    //         Stat::make('Total Revenus', number_format($totalRevenus, 0, ',', ' ') . ' FCFA')
    //             ->description('Loyers perçus')
    //             ->descriptionIcon('heroicon-m-currency-dollar')
    //             ->color('primary'),
    //     ];
    // }

    protected function getStats(): array
    {
        $totalContracts = Contract::count();
        $activeContracts = Contract::where('status', ContractStatus::ACTIVE)->count();
        $expiringContracts = Contract::expiringSoon()->count();
        $totalRevenue = Contract::where('status', ContractStatus::ACTIVE)->sum('monthly_rent');

        return [
            Stat::make('Total Contrats', $totalContracts)
                ->description('Tous les contrats')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Contrats Actifs', $activeContracts)
                ->description('En cours')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Expire Bientôt', $expiringContracts)
                ->description('Dans les 90 jours')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),

            Stat::make('Revenus Mensuels', number_format($totalRevenue, 0, ',', ' ') . ' FCFA')
                ->description('Loyers actifs')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}
