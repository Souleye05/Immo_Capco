<?php

namespace App\Filament\Admin\Resources\PropertyResource\Widgets;

use App\Models\Payment;
use App\Services\PaymentService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class PropertyPaymentStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $paymentService = app(PaymentService::class);

        [$month,$year] = $this->getCurrentMonthAndYear();

        // $payments = $paymentService->getMonthlyPayments($month, $year);
        // reversement fait /total des reversements
       

        $monthlyRevenue = $paymentService->calculateMonthlyRevenue($month, $year);   
        $monthlyCommissions = $paymentService->calculateMonthlyCommissions($month, $year);
        $monthlyExpenses = $paymentService->calculateMonthlyExpenses(null, $month, $year);

        $netToTransfer = $monthlyRevenue - ($monthlyCommissions + $monthlyExpenses);

        return [
            Stat::make('💸 Commissions et dépenses', $paymentService->formatAmount($monthlyCommissions + $monthlyExpenses))
                ->description('Commissions : ' . $paymentService->formatAmount((float)$monthlyCommissions) . ' | Dépenses : ' . $paymentService->formatAmount($monthlyExpenses))
                ->icon('heroicon-o-currency-dollar')
                ->color('warning'),

            Stat::make('🏦 Montant net à reverser', $paymentService->formatAmount($netToTransfer))
                ->description('Après déduction des charges')
                ->icon('heroicon-o-banknotes')
                ->color('success'),
        ];
    }

    private function getCurrentMonthAndYear(): array
    {
        $now = now();
        return [$now->month, $now->year];
    }
}
