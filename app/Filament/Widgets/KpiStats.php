<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Tenant;
use App\Services\ExpenseService;
use App\Services\PaymentService;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class KpiStats extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    /**
     * Obtenir l'agence courante (tenant)
     */
    protected function getCurrentTenant()
    {
        return Filament::getTenant();
    }

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
     * Statistique : Propriétés totales (filtrées par agence)
     */
    private function getTotalPropertiesStat(): Stat
    {
        $tenant = $this->getCurrentTenant();
        $count = $tenant ? Property::where('agency_id', $tenant->id)->count() : Property::count();

        return Stat::make('Propriétés totales', $count)
            ->description('Propriétés de cette agence')
            ->descriptionIcon('heroicon-m-arrow-trending-up')
            ->icon('heroicon-o-building-office')
            ->color('primary')
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-primary-50 to-white dark:from-primary-900 dark:to-primary-800 border-t-4 border-primary-500 shadow-md rounded-lg',
            ]);
    }

    /**
     * Statistique : Locataires actifs (filtrés par agence)
     */
    private function getActiveTenantsStat(): Stat
    {
        $tenant = $this->getCurrentTenant();
        $count = $tenant ? Tenant::where('agency_id', $tenant->id)->count() : Tenant::count();

        return Stat::make('Locataires actifs', $count)
            ->description('Locataires de cette agence')
            ->icon('heroicon-o-users')
            ->color('success')
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-success-50 to-white dark:from-success-900 dark:to-success-800 border-t-4 border-success-500 shadow-md rounded-lg',
            ]);
    }

    /**
     * Statistique : Factures payées (filtrées par agence)
     */
    private function getPaidInvoicesStat(): Stat
    {
        $tenant = $this->getCurrentTenant();

        $totalQuery = Payment::query();
        $paidQuery = Payment::where('status', true);

        if ($tenant) {
            $totalQuery->where('agency_id', $tenant->id);
            $paidQuery->where('agency_id', $tenant->id);
        }

        $totalPayments = $totalQuery->count();
        $paidPayments = $paidQuery->count();

        return Stat::make('Factures payées', $paidPayments . '/' . $totalPayments)
            ->description('Reste: ' . ($totalPayments - $paidPayments) . ' factures de cette agence')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-success-50 to-white dark:from-success-900 dark:to-success-800 border-t-4 border-success-500 shadow-md rounded-lg',
            ]);
    }

    /**
     * Statistique : Commissions du mois (filtrées par agence)
     */
    private function getMonthlyCommissionsStat(): Stat
    {
        $paymentService = app(PaymentService::class);
        $now = Carbon::now();
        $currentMonth = $now->month;
        $currentYear = $now->year;
        $tenant = $this->getCurrentTenant();

        // Calculer les commissions filtrées par agence
        // Pour l'instant, nous utilisons une approche simplifiée
        // Les services devront être adaptés plus tard pour le filtrage complet par agence
        $commissionQuery = Payment::whereMonth('date_payment', $currentMonth)
            ->whereYear('date_payment', $currentYear)
            ->where('type', 'commission');

        if ($tenant) {
            $commissionQuery->where('agency_id', $tenant->id);
        }

        $currentMonthCommissions = $commissionQuery->sum('amount');
        $paidCommissions = $commissionQuery->where('status', true)->sum('amount');
        $unpaidCommissions = $currentMonthCommissions - $paidCommissions;

        return Stat::make('Commissions du mois', $paymentService->formatAmount($currentMonthCommissions))
            ->description('Encaissées: ' . $paymentService->formatAmount($paidCommissions) . ' | À percevoir: ' . $paymentService->formatAmount($unpaidCommissions) . ' (cette agence)')
            ->icon('heroicon-o-currency-dollar')
            ->color('success')
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-emerald-50 to-white dark:from-emerald-900 dark:to-emerald-800 border-t-4 border-emerald-500 shadow-md rounded-lg',
            ]);
    }

    /**
     * Statistique : Revenus du mois (filtrés par agence)
     */
    private function getMonthlyRevenueStat(): Stat
    {
        $now = Carbon::now();
        $currentMonthName = $now->locale('fr')->isoFormat('MMMM');
        $tenant = $this->getCurrentTenant();

        $query = Payment::whereMonth('date_payment', $now->month)
            ->whereYear('date_payment', $now->year);

        if ($tenant) {
            $query->where('agency_id', $tenant->id);
        }

        $monthlyRevenue = $query->sum('amount');

        return Stat::make('Revenus du mois', number_format($monthlyRevenue, 0, ',', ' ') . ' FCFA')
            ->description('Revenus de ' . $currentMonthName . ' pour cette agence')
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-success-50 to-white dark:from-success-900 dark:to-success-800 border-t-4 border-success-500 shadow-md rounded-lg',
            ]);
    }

    /**
     * Statistique : Montant total encaissé (filtré par agence)
     */
    private function getTotalCollectedStat(): Stat
    {
        $tenant = $this->getCurrentTenant();
        $paymentService = app(PaymentService::class);

        $query = Payment::where('status', true);
        if ($tenant) {
            $query->where('agency_id', $tenant->id);
        }

        $totalCollected = $query->sum('amount');

        return Stat::make('Montant encaissé', $paymentService->formatAmount($totalCollected))
            ->description('Total des paiements reçus de cette agence')
            ->icon('heroicon-o-currency-dollar')
            ->color('primary')
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-info-50 to-white dark:from-info-900 dark:to-info-800 border-t-4 border-info-500 shadow-md rounded-lg',
            ]);
    }

    /**
     * Statistique : Montant restant à encaisser (filtré par agence)
     */
    private function getRemainingToCollectStat(): Stat
    {
        $tenant = $this->getCurrentTenant();
        $paymentService = app(PaymentService::class);

        $query = Payment::where('status', false);
        if ($tenant) {
            $query->where('agency_id', $tenant->id);
        }

        $remainingToCollect = $query->sum('amount');

        return Stat::make('Montant à encaisser', $paymentService->formatAmount($remainingToCollect))
            ->description('Paiements en attente de cette agence')
            ->icon('heroicon-o-clock')
            ->color('danger')
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-warning-50 to-white dark:from-warning-900 dark:to-warning-800 border-t-4 border-warning-500 shadow-md rounded-lg',
            ]);
    }


    // Montant net à reverser
    /**
     * Statistique : Montant net à reverser (filtré par agence)
     */
    private function getNetToTransferStat(): Stat
    {
        $now = Carbon::now();
        $month = $now->month;
        $year = $now->year;
        $tenant = $this->getCurrentTenant();
        $paymentService = app(PaymentService::class);

        // Calculer les revenus mensuels filtrés par agence
        $revenueQuery = Payment::whereMonth('date_payment', $month)
            ->whereYear('date_payment', $year)
            ->where('status', true);

        if ($tenant) {
            $revenueQuery->where('agency_id', $tenant->id);
        }

        $monthlyRevenue = $revenueQuery->sum('amount');

        // Pour les commissions et dépenses, nous utilisons les services existants
        // mais nous devrons les adapter plus tard pour le filtrage par agence
        $monthlyCommissions = $paymentService->calculateMonthlyCommissions($month, $year);
        $monthlyExpenses = $paymentService->calculateMonthlyExpenses(null, $month, $year);

        $netToTransfer = $monthlyRevenue - ($monthlyCommissions + $monthlyExpenses);

        return Stat::make('Montant net à reverser', $paymentService->formatAmount($netToTransfer))
            ->description('Après déduction des charges de cette agence')
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-success-50 to-white dark:from-success-900 dark:to-success-800 border-t-4 border-success-500 shadow-md rounded-lg',
            ]);
    }

    /**
     * Statistique : Dépenses totales (filtrées par agence)
     */
    private function getTotalExpensesStat(): Stat
    {
        $tenant = $this->getCurrentTenant();
        $now = Carbon::now();

        // Calculer les dépenses filtrées par agence via les propriétés
        $query = Expense::whereMonth('payment_date', $now->month)
            ->whereYear('payment_date', $now->year);

        if ($tenant) {
            $query->whereHas('property', function ($subQuery) use ($tenant) {
                $subQuery->where('agency_id', $tenant->id);
            });
        }

        $totalExpenses = $query->sum('amount');

        return Stat::make('Dépenses totales du mois', number_format($totalExpenses, 0, ',', ' ') . ' FCFA')
            ->description('Dépenses de cette agence pour ce mois')
            ->icon('heroicon-o-clipboard-document-list')
            ->color('gray')
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-gray-50 to-white dark:from-gray-900 dark:to-gray-800 border-t-4 border-gray-500 shadow-md rounded-lg',
            ]);
    }
}
