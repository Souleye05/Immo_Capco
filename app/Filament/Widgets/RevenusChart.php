<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class RevenusChart extends ChartWidget
{
    protected static ?string $heading = 'Revenus du mois';
    // protected static ?string $maxHeight = '400px';
    public ?string $filter = 'mois';

    protected function getFilters(): ?array
    {
        return [
            //
            'aujourdhui' => 'Aujourd\'hui',
            'semaine' => 'Cette semaine',
            'mois' => 'Ce mois-ci',
            'moisPrecedent' => 'Le mois dernier',
            'annee' => 'Cette année',
        ];
    }
    protected function getData(): array
    {
        $query = Payment::query();
        $now = Carbon::now();
        $labels = [];
        $data = [];

        // 🧠 On adapte les requêtes selon le filtre choisi
        switch ($this->filter) {
            case 'aujourdhui':
                $query->whereDate('date_payment', $now);
                $labels[] = $now->format('d/m/Y');
                $data[] = $query->sum('amount');
                break;

            case 'semaine':
                for ($i = 6; $i >= 0; $i--) {
                    $date = $now->copy()->subDays($i);
                    $labels[] = $date->format('D');
                    $data[] = Payment::whereDate('date_payment', $date)->sum('amount');
                }
                break;

            case 'mois':
                for ($i = 1; $i <= 12; $i++) {
                    $labels[] = Carbon::create()->month($i)->format('F');
                    $data[] = Payment::whereMonth('date_payment', $i)
                        ->whereYear('date_payment', $now->year)
                        ->sum('amount');
                }
                break;
            
            case 'moisPrecedent':
                for ($i = 1; $i <= 12; $i++) {
                    $labels[] = Carbon::create()->month($i)->format('F');
                    $data[] = Payment::whereMonth('date_payment', $i)
                        ->whereYear('date_payment', $now->year - 1)
                        ->sum('amount');
            }    

            case 'annee':
                for ($i = 5; $i >= 0; $i--) {
                    $year = $now->year - $i;
                    $labels[] = (string) $year;
                    $data[] = Payment::whereYear('date_payment', $year)->sum('amount');
                }
                break;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenus en FCFA',
                    'data' => array_map(fn($value) => round($value / 1000, 1), $data), // 💸 Format en milliers
                    'backgroundColor' => $this->generateColors(count($data)),
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    private function generateColors(int $count): array
    {
        $colors = [
            '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
            '#ec4899', '#14b8a6', '#f97316', '#22c55e', '#6366f1',
            '#a855f7', '#eab308',
        ];

        return array_slice(array_merge($colors, $colors), 0, $count);
    }
}
