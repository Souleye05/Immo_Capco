<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Models\Expense;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class RevenusVsDepensesChart extends ChartWidget
{
    protected static ?string $heading = '📊 Revenus vs Dépenses';
    protected static ?string $maxHeight = '300px';

    protected function getType(): string
    {
        return 'line'; // Graphique en ligne
    }

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Aujourd\'hui',
            'week' => 'Cette semaine',
            'month' => 'Ce mois-ci',
            'year' => 'Cette année',
        ];
    }

    protected function getData(): array
    {
        $revenus = [];
        $depenses = [];
        $labels = [];

        $filter = $this->filter ?? 'year';

        switch ($filter) {
            case 'today':
                $labels[] = now()->format('d/m/Y');
                $revenus[] = Payment::whereDate('date_payment', today())->sum('amount');
                $depenses[] = Expense::whereDate('payment_date', today())->sum('amount');
                break;

            case 'week':
                foreach (range(0, 6) as $i) {
                    $day = now()->startOfWeek()->addDays($i);
                    $labels[] = ucfirst($day->locale('fr')->isoFormat('dddd'));

                    $revenus[] = Payment::whereDate('date_payment', $day)->sum('amount');
                    $depenses[] = Expense::whereDate('payment_date', $day)->sum('amount');
                }
                break;

            case 'month':
                $daysInMonth = now()->daysInMonth;
                for ($i = 1; $i <= $daysInMonth; $i++) {
                    $date = now()->copy()->day($i);
                    $labels[] = $date->format('d');

                    $revenus[] = Payment::whereDate('date_payment', $date)->sum('amount');
                    $depenses[] = Expense::whereDate('payment_date', $date)->sum('amount');
                }
                break;

            case 'year':
            default:
                for ($i = 1; $i <= 12; $i++) {
                    $monthName = Carbon::create()->month($i)->locale('fr')->isoFormat('MMMM');
                    $labels[] = ucfirst($monthName);

                    $revenus[] = Payment::whereYear('date_payment', now()->year)
                        ->whereMonth('date_payment', $i)
                        ->sum('amount');

                    $depenses[] = Expense::whereYear('payment_date', now()->year)
                        ->whereMonth('payment_date', $i)
                        ->sum('amount');
                }
                break;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenus',
                    'data' => $revenus,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Dépenses',
                    'data' => $depenses,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
