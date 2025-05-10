<?php

namespace App\Filament\Resources\RemittanceResource\Widgets;

use App\Models\Remittance;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReversementStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Calculs des statistiques
        $totalDue = Remittance::sum('amount_to_transfer'); // Montant total dû
        $totalPaid = Remittance::sum('amount'); // Montant total versé
        $remaining = $totalDue - $totalPaid; // Reste à verser

        $totalRemittances = Remittance::count(); // Nombre total de reversements
        $completedRemittances = Remittance::where('status', 'Paid')->count(); // Reversements complétés
        $pendingRemittances = Remittance::where('status', 'Pending')->count(); // Reversements en attente

        return [
            Stat::make('Montant total dû', number_format($totalDue, 0, ',', ' ') . ' FCFA')
                ->description('Montant total des reversements')
                ->icon('heroicon-o-currency-dollar')
                ->color('primary'),

            Stat::make('Montant total versé', number_format($totalPaid, 0, ',', ' ') . ' FCFA')
                ->description('Montant déjà versé')
                ->icon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Reste à verser', number_format($remaining, 0, ',', ' ') . ' FCFA')
                ->description('Montant restant à reverser')
                ->icon('heroicon-o-clock')
                ->color('danger'),

                // REVERSEMENTS / TOTAL DES REVERSEMENTS ET LE RESTE 
                Stat::make('Total des reversements', $completedRemittances . '/' . $totalRemittances)
                ->description('Reste : ' . ($totalRemittances - $completedRemittances ) . ' reversements')
                ->icon('heroicon-o-document-text')
                ->color('info'),
                    


            // Stat::make('Total des reversements', $totalRemittances)
            //     ->description('Nombre total de reversements')
            //     // ->icon('heroicon-o-clipboard-list')
            //     ->color('info'),

            // Stat::make('Reversements complétés', $completedRemittances)
            //     ->description('Nombre de reversements complétés')
            //     ->icon('heroicon-o-check-circle')
            //     ->color('success'),

            // Stat::make('Reversements en attente', $pendingRemittances)
            //     ->description('Nombre de reversements en attente')
            //     ->icon('heroicon-o-clock')
            //     ->color('warning'),
        ];
    }
}
