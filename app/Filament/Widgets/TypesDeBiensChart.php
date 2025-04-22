<?php

namespace App\Filament\Widgets;

use App\Models\Property;
use Filament\Widgets\ChartWidget;

class TypesDeBiensChart extends ChartWidget
{
    protected static ?string $heading = '🏘️ Types de biens';
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $types = Property::select('type')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('type')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Nombre de biens',
                    'data' => $types->pluck('total'),
                    'backgroundColor' => ['#f97316', '#3b82f6', '#10b981', '#e11d48', '#a855f7'],
                ],
            ],
            'labels' => $types->pluck('type'),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
