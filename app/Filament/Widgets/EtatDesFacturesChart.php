<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Models\Property;
use Filament\Widgets\ChartWidget;

class EtatDesFacturesChart extends ChartWidget
{
    protected static ?string $heading = '📊 État des factures par propriété';
    protected static ?string $maxHeight = '300px';
    protected function getData(): array
    {
        $properties = Property::with('flats')->get();

        $labels = [];
        $payees = [];
        $enAttente = [];
        $partielles = [];

        foreach ($properties as $property) {
            $labels[] = $property->name ?? "Propriété #{$property->id}";
            $flatIds = $property->flats->pluck('id');

            $payees[] = Payment::whereIn('flat_id', $flatIds)->where('status', 1)->count();
            $enAttente[] = Payment::whereIn('flat_id', $flatIds)->where('status', 0)->count();
            $partielles[] = Payment::whereIn('flat_id', $flatIds)->where('status', 2)->count();
        }
        return [
            //
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Payés',
                    'data' => $payees,
                    'backgroundColor' => '#10b981', // vert
                    'stack' => 'etat',
                ],
                [
                    'label' => 'En attente',
                    'data' => $enAttente,
                    'backgroundColor' => '#f97316', // orange
                    'stack' => 'etat',
                ],
                [
                    'label' => 'Partiellement payés',
                    'data' => $partielles,
                    'backgroundColor' => '#a855f7', // violet
                    'stack' => 'etat',
                ]
            ]
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
