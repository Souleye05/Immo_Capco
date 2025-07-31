<?php

namespace App\Filament\Admin\Resources\PaymentResource\Widgets;

use App\Services\PaymentService;
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
        $previousMonth = $currentMonth === 1 ? 12 : $currentMonth - 1;
        $previousYear = $currentMonth === 1 ? $currentYear - 1 : $currentYear;
        
        // Utilisation du service de paiement
        $paymentService = app(PaymentService::class);
        
        // Récupérer les statistiques globales
        $paymentStats = $paymentService->getPaymentStats();
        $totalPayments = $paymentStats['total_payments'];
        $paidPayments = $paymentStats['paid_payments'];
        $totalCollected = $paymentStats['total_collected'];
        $remainingToCollect = $paymentStats['remaining_to_collect'];
        
        // Revenus du mois actuel et précédent
        $monthlyRevenue = $paymentService->calculateMonthlyRevenue($currentMonth, $currentYear);
        $previousMonthRevenue = $paymentService->calculateMonthlyRevenue($previousMonth, $previousYear);
        $revenueDifference = $paymentService->calculatePercentageDifference($monthlyRevenue, $previousMonthRevenue);
        $revenueDescription = $paymentService->formatPercentageDifference($revenueDifference);
        
        // Commissions du mois en cours et du mois précédent
        $currentMonthCommissions = $paymentService->calculateMonthlyCommissions($currentMonth, $currentYear);
        $previousMonthCommissions = $paymentService->calculateMonthlyCommissions($previousMonth, $previousYear);
        $commissionDifference = $paymentService->calculatePercentageDifference($currentMonthCommissions, $previousMonthCommissions);
        
        // Commissions payées et impayées
        $paidCommissions = $paymentService->calculateCommissionsByStatus($currentMonth, $currentYear, 1);
        $unpaidCommissions = $currentMonthCommissions - $paidCommissions;

        return [
            // Revenus du mois
            Stat::make('Revenus du mois', $paymentService->formatAmount($monthlyRevenue))
                ->description($revenueDescription)
                ->descriptionIcon($revenueDifference >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->icon('heroicon-o-banknotes')
                ->color($revenueDifference >= 0 ? 'success' : 'danger')
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-primary-50 to-white dark:from-primary-900 dark:to-primary-800 border-t-4 border-primary-500 shadow-md rounded-lg',
                ]),

            // Commissions du mois en cours
            Stat::make('Commissions du mois', $paymentService->formatAmount($currentMonthCommissions))
                ->description('Encaissées: ' . $paymentService->formatAmount($paidCommissions) . ' | À percevoir: ' . $paymentService->formatAmount($unpaidCommissions))
                ->descriptionIcon($commissionDifference >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->icon('heroicon-o-currency-dollar')
                ->color($commissionDifference >= 0 ? 'success' : 'danger')
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-emerald-50 to-white dark:from-emerald-900 dark:to-emerald-800 border-t-4 border-emerald-500 shadow-md rounded-lg',
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
            Stat::make('Montant encaissé', $paymentService->formatAmount($totalCollected))
                ->description('Total des paiements reçus')
                ->icon('heroicon-o-currency-dollar')
                ->color('primary')
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-info-50 to-white dark:from-info-900 dark:to-info-800 border-t-4 border-info-500 shadow-md rounded-lg',
                ]),

            // Montant restant à encaisser
            Stat::make('Montant à encaisser', $paymentService->formatAmount($remainingToCollect))
                ->description('Paiements en attente')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-warning-50 to-white dark:from-warning-900 dark:to-warning-800 border-t-4 border-warning-500 shadow-md rounded-lg',
                ]),
        ];
    }
}