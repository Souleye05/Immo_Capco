<?php

namespace App\Filament\Resources\ExpenseResource\Widgets;

use App\Services\ExpenseService;
use App\Services\PaymentService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class ExpenseStatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '15m';
    
    protected int | string | array $columnSpan = 'full';
    
    protected function getStats(): array
    {
        $now = Carbon::now();
        $currentMonth = $now->month;
        $currentYear = $now->year;
        
        $expenseService = app(ExpenseService::class);
        $paymentService = app(PaymentService::class);
        return [
            $this->createTotalExpensesStat($expenseService->totalExpenses(), $paymentService),
            $this->createMonthlyExpensesStat($expenseService->calculateMonthlyExpenses($currentMonth, $currentYear), $paymentService),
            $this->createTopPropertiesStat($expenseService->getTopNPropertiesWithMostExpenses()),
            $this->createTopCategoryStat($expenseService->getTopCategoryInfo(), $paymentService),
        ];
    }
    
    private function createTotalExpensesStat(float $totalExpenses, PaymentService $paymentService): Stat
    {
        return Stat::make('Total des dépenses', $paymentService->formatAmount($totalExpenses))
            ->description('Tous les paiements enregistrés')
            ->icon('heroicon-o-clipboard-document-list')
            ->color('gray')
            ->extraAttributes($this->getCardAttributes('gray'));
    }
    
    private function createMonthlyExpensesStat(float $monthlyExpenses, PaymentService $paymentService): Stat
    {
        return Stat::make('Dépenses du mois courant', $paymentService->formatAmount($monthlyExpenses))
            ->description('Dépenses totales du mois')
            ->icon('heroicon-o-calendar')
            ->color('success')
            ->extraAttributes($this->getCardAttributes('success'));
    }
    
    private function createTopPropertiesStat(string $topProperty): Stat
    {
        return Stat::make('Top propriétés coûteuses', 'Top 3')
            ->description($topProperty)
            ->icon('heroicon-o-building-office-2')
            ->color('danger')
            ->extraAttributes($this->getCardAttributes('danger'));
    }
    
    private function createTopCategoryStat(array $topType, PaymentService $paymentService): Stat
    {
        // dd($topType['total']);
        return Stat::make('Catégorie principale', $topType['name'])
            ->description($paymentService->formatAmount((float)$topType['total']))
            ->icon('heroicon-o-tag')
            ->color('primary')
            ->extraAttributes($this->getCardAttributes('primary'));
    }
    
    private function getCardAttributes(string $color): array
    {
        return [
            'class' => "bg-gradient-to-br from-{$color}-50 to-white dark:from-{$color}-900 dark:to-{$color}-800 border-t-4 border-{$color}-500 shadow-md rounded-lg",
        ];
    }
}