<?php

namespace App\Filament\Widgets;

use App\Enums\ContractStatus;
use App\Models\Contract;
use Filament\Widgets\ChartWidget;

class ContractChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Évolution des Contrats';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $months = collect(range(1, 12))->map(function ($month) {
            return now()->startOfYear()->addMonths($month - 1)->format('M');
        });

        $contractsData = $months->map(function ($month, $index) {
            return Contract::whereMonth('created_at', $index + 1)
                ->whereYear('created_at', now()->year)
                ->count();
        });

        return [
            'datasets' => [
                [
                    'label' => 'Nouveaux contrats',
                    'data' => $contractsData->toArray(),
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.1)',
                ],
            ],
            'labels' => $months->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
