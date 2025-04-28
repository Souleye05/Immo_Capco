<?php

namespace App\Filament\Resources\PaymentResource\Widgets;

use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class PaymentStatsOverview extends BaseWidget
{
    protected function getStats(): array
{
    $now = Carbon::now();
    $currentMonth = $now->month;
    $currentYear = $now->year;

    // Total des factures
    $totalPayments = Payment::count();
    
    // Factures payées
    $paidPayments = Payment::where('status', 1)->count();
    
    // Montant total encaissé (de tous les temps)
    $totalCollected = Payment::where('status', 1)->sum('amount');
    
    // Montant restant à encaisser
    $remainingToCollect = Payment::where('status', '!=', 1)->sum('amount');
        
    // Revenus du mois actuel
    $monthlyRevenue = Payment::whereMonth('date_payment', $currentMonth)
        ->whereYear('date_payment', $currentYear)
        ->sum('amount');
        
    $previousMonthRevenue = Payment::whereMonth('date_payment', $currentMonth === 1 ? 12 : $currentMonth - 1)
        ->whereYear('date_payment', $currentMonth === 1 ? $currentYear - 1 : $currentYear)
        ->sum('amount');
        
    $revenueDifference = $previousMonthRevenue ? (($monthlyRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100 : 0;
    $revenueDescription = $revenueDifference >= 0 
        ? "+" . number_format($revenueDifference, 1) . "% par rapport au mois précédent"
        : number_format($revenueDifference, 1) . "% par rapport au mois précédent";

    return [
        // Revenus du mois
        Stat::make('Revenus du mois', number_format($monthlyRevenue, 0, ',', ' ') . ' FCFA')
            ->description($revenueDescription)
            ->descriptionIcon($revenueDifference >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->icon('heroicon-o-banknotes')
            ->color($revenueDifference >= 0 ? 'success' : 'danger')
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-primary-50 to-white dark:from-primary-900 dark:to-primary-800 border-t-4 border-primary-500 shadow-md rounded-lg',
            ]),

        // Factures payées / total
        Stat::make('Factures payées', $paidPayments . '/' . $totalPayments)
            ->description('Reste: ' . ($totalPayments - $paidPayments) . ' factures')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-success-50 to-white dark:from-success-900 dark:to-success-800 border-t-4 border-success-500 shadow-md rounded-lg',
            ]),

        // Montant total encaissé
        Stat::make('Montant encaissé', number_format($totalCollected, 0, ',', ' ') . ' FCFA')
            ->description('Total des paiements reçus')
            ->icon('heroicon-o-currency-dollar')
            ->color('primary')
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-info-50 to-white dark:from-info-900 dark:to-info-800 border-t-4 border-info-500 shadow-md rounded-lg',
            ]),

        // Montant restant à encaisser
        Stat::make('Montant à encaisser', number_format($remainingToCollect, 0, ',', ' ') . ' FCFA')
            ->description('Paiements en attente')
            ->icon('heroicon-o-clock')
            ->color('warning')
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-warning-50 to-white dark:from-warning-900 dark:to-warning-800 border-t-4 border-warning-500 shadow-md rounded-lg',
            ]),
    ];
}
}
