<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Tenant;
use App\Services\ExpenseService;
use App\Services\PaymentService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class KpiStats extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        return [
            $this->getTotalPropertiesStat(),
            $this->getActiveTenantsStat(),
            $this->getPaidInvoicesStat(),
            $this->getMonthlyRevenueStat(),
            $this->getTotalCollectedStat(),
            $this->getRemainingToCollectStat(),
            $this->getMonthlyCommissionsStat(),
            $this->getNetToTransferStat(),
            $this->getTotalExpensesStat(),
        ];
    }

    /**
     * Statistique : Propriétés totales
     */
    private function getTotalPropertiesStat(): Stat
    {
        return Stat::make('Propriétés totales', Property::count())
            ->description('Propriétés enregistrées')
            ->descriptionIcon('heroicon-m-arrow-trending-up')
            ->icon('heroicon-o-building-office')
            ->color('primary')
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-primary-50 to-white dark:from-primary-900 dark:to-primary-800 border-t-4 border-primary-500 shadow-md rounded-lg',
            ]);
    }

    /**
     * Statistique : Locataires actifs
     */
    private function getActiveTenantsStat(): Stat
    {
        return Stat::make('Locataires actifs', Tenant::count())
            ->description('Locataires avec une location en cours')
            ->icon('heroicon-o-users')
            ->color('success')
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-success-50 to-white dark:from-success-900 dark:to-success-800 border-t-4 border-success-500 shadow-md rounded-lg',
            ]);
    }

    /**
     * Statistique : Factures payées
     */
    private function getPaidInvoicesStat(): Stat
    {
        $paymentService = app(PaymentService::class);
        $paymentStats = $paymentService->getPaymentStats();
        $totalPayments = $paymentStats['total_payments'];
        $paidPayments = $paymentStats['paid_payments'];

        return Stat::make('Factures payées', $paidPayments . '/' . $totalPayments)
            ->description('Reste: ' . ($totalPayments - $paidPayments) . ' factures')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-success-50 to-white dark:from-success-900 dark:to-success-800 border-t-4 border-success-500 shadow-md rounded-lg',
            ]);
    }

    /**
     * Statistique : Commissions du mois
     */
    private function getMonthlyCommissionsStat(): Stat
    {
        $paymentService = app(PaymentService::class);
        $now = Carbon::now();
        $currentMonth = $now->month;
        $currentYear = $now->year;

        $currentMonthCommissions = $paymentService->calculateMonthlyCommissions($currentMonth, $currentYear);
        $paidCommissions = $paymentService->calculateCommissionsByStatus($currentMonth, $currentYear, 1);
        $unpaidCommissions = $currentMonthCommissions - $paidCommissions;

        return Stat::make('Commissions du mois', $paymentService->formatAmount($currentMonthCommissions))
            ->description('Encaissées: ' . $paymentService->formatAmount($paidCommissions) . ' | À percevoir: ' . $paymentService->formatAmount($unpaidCommissions))
            ->icon('heroicon-o-currency-dollar')
            ->color('success')
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-emerald-50 to-white dark:from-emerald-900 dark:to-emerald-800 border-t-4 border-emerald-500 shadow-md rounded-lg',
            ]);
    }

    /**
     * Statistique : Revenus du mois
     */
    private function getMonthlyRevenueStat(): Stat
    {
        $now = Carbon::now();
        $currentMonthName = $now->locale('fr')->isoFormat('MMMM');

        $monthlyRevenue = Payment::whereMonth('date_payment', $now->month)
            ->whereYear('date_payment', $now->year)
            ->sum('amount');

        return Stat::make('Revenus du mois', number_format($monthlyRevenue, 0, ',', ' ') . ' FCFA')
            ->description('Revenus du mois de ' . $currentMonthName)
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-success-50 to-white dark:from-success-900 dark:to-success-800 border-t-4 border-success-500 shadow-md rounded-lg',
            ]);
    }

    /**
     * Statistique : Montant total encaissé
     */
    private function getTotalCollectedStat(): Stat
    {
        $paymentService = app(PaymentService::class);
        $totalCollected = $paymentService->getPaymentStats()['total_collected'];

        return Stat::make('Montant encaissé', $paymentService->formatAmount($totalCollected))
            ->description('Total des paiements reçus')
            ->icon('heroicon-o-currency-dollar')
            ->color('primary')
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-info-50 to-white dark:from-info-900 dark:to-info-800 border-t-4 border-info-500 shadow-md rounded-lg',
            ]);
    }

    /**
     * Statistique : Montant restant à encaisser
     */
    private function getRemainingToCollectStat(): Stat
    {
        $paymentService = app(PaymentService::class);
        $remainingToCollect = $paymentService->getPaymentStats()['remaining_to_collect'];

        return Stat::make('Montant à encaisser', $paymentService->formatAmount($remainingToCollect))
            ->description('Paiements en attente')
            ->icon('heroicon-o-clock')
            ->color('danger')
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-warning-50 to-white dark:from-warning-900 dark:to-warning-800 border-t-4 border-warning-500 shadow-md rounded-lg',
            ]);
    }


    // Montant net à reverser
    /**
     * Statistique : Montant net à reverser
     */
    private function getNetToTransferStat(): Stat

    {
        $now = Carbon::now();
        $month = $now->month;
        $year = $now->year;
        $paymentService = app(PaymentService::class);
        // $netToTransfer = $paymentService->getPaymentStats()['net_to_transfer'];
        $monthlyRevenue = $paymentService->calculateMonthlyRevenue($month, $year);   
        $monthlyCommissions = $paymentService->calculateMonthlyCommissions($month, $year);
        $monthlyExpenses = $paymentService->calculateMonthlyExpenses(null, $month, $year);

        $netToTransfer = $monthlyRevenue - ($monthlyCommissions + $monthlyExpenses);


        return Stat::make('Montant net à reverser', $paymentService->formatAmount($netToTransfer))
            ->description('Après deduction des charges')
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-success-50 to-white dark:from-success-900 dark:to-success-800 border-t-4 border-success-500 shadow-md rounded-lg',
            ]);
    }

    /**
     * Statistique : Dépenses totales
     */
    private function getTotalExpensesStat(): Stat
    {
        $expenseService = app(ExpenseService::class);
        $totalExpenses = $expenseService->totalExpenses();

        return Stat::make('Dépenses totales du mois', number_format($totalExpenses, 0, ',', ' ') . ' FCFA')
            ->description('Dépenses totales enregistrées')
            ->icon('heroicon-o-clipboard-document-list')
            ->color('gray')
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-gray-50 to-white dark:from-gray-900 dark:to-gray-800 border-t-4 border-gray-500 shadow-md rounded-lg',
            ]);
    }
}