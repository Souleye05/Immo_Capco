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

        $totalPayments = Payment::count();
        $paidPayments = Payment::where('status', 1)->count();
        $partialPayments = Payment::where('status', '!= ', 1)
                            ->where('status', '!= ', 0)
                            ->count();
        $unpaidPayments = Payment::where('status', 0)->count();

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
                Stat::make('Total des paiements', $totalPayments)
                    ->description('Nombre total de factures')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->extraAttributes([
                        'class' => 'bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-900 border-t-4 border-gray-500 shadow-md rounded-lg',
                    ]),
    
                Stat::make('Factures payées', $paidPayments)
                    ->description(number_format(($paidPayments / max(1, $totalPayments)) * 100, 1) . '% du total')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->extraAttributes([
                        'class' => 'bg-gradient-to-br from-success-50 to-white dark:from-success-900 dark:to-success-800 border-t-4 border-success-500 shadow-md rounded-lg',
                    ]),
    
                Stat::make('Factures partielles', $partialPayments)
                    ->description('Nécessitent un complément')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('warning')
                    ->extraAttributes([
                        'class' => 'bg-gradient-to-br from-warning-50 to-white dark:from-warning-900 dark:to-warning-800 border-t-4 border-warning-500 shadow-md rounded-lg',
                    ]),
    
                Stat::make('Factures impayées', $unpaidPayments)
                    ->description(number_format(($unpaidPayments / max(1, $totalPayments)) * 100, 1) . '% du total')
                    ->icon('heroicon-o-exclamation-circle')
                    ->color('danger')
                    ->extraAttributes([
                        'class' => 'bg-gradient-to-br from-danger-50 to-white dark:from-danger-900 dark:to-danger-800 border-t-4 border-danger-500 shadow-md rounded-lg',
                    ]),
    
                Stat::make('Revenus du mois', number_format($monthlyRevenue, 0, ',', ' ') . ' FCFA')
                    ->description($revenueDescription)
                    ->descriptionIcon($revenueDifference >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->icon('heroicon-o-banknotes')
                    ->color($revenueDifference >= 0 ? 'success' : 'danger')
                    ->extraAttributes([
                        'class' => 'bg-gradient-to-br from-primary-50 to-white dark:from-primary-900 dark:to-primary-800 border-t-4 border-primary-500 shadow-md rounded-lg',
                    ]),
            ];
        }
}
