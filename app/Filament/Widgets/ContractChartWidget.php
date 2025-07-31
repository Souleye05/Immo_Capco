<?php

namespace App\Filament\Widgets;

use App\Enums\ContractStatus;
use App\Models\Contract;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class ContractChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Évolution des Contrats';
    protected static ?int $sort = 2;

    /**
     * Obtenir l'agence courante (tenant)
     */
    protected function getCurrentTenant()
    {
        return Filament::getTenant();
    }

    protected function getData(): array
    {
        $tenant = $this->getCurrentTenant();

        $months = collect(range(1, 12))->map(function ($month) {
            return now()->startOfYear()->addMonths($month - 1)->format('M');
        });

        $contractsData = $months->map(function ($month, $index) use ($tenant) {
            $query = Contract::whereMonth('created_at', $index + 1)
                ->whereYear('created_at', now()->year);

            if ($tenant) {
                $query->where('agency_id', $tenant->id);
            }

            return $query->count();
        });

        return [
            'datasets' => [
                [
                    'label' => 'Nouveaux contrats' . ($tenant ? ' - ' . $tenant->name : ''),
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
