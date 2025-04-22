<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unsold;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class KpiStats extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $now = Carbon::now();
        $currentMonthName = $now->locale('fr')->isoFormat('MMMM');

        return [
            Stat::make('Propriétés totales', Property::count())
                ->description('Propriétés enregistrées')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->icon('heroicon-o-building-office')
                ->color('primary')
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-primary-50 to-white dark:from-primary-900 dark:to-primary-800 border-t-4 border-primary-500 shadow-md rounded-lg',
                ]),

            Stat::make('Locataires actifs', Tenant::count())
                ->description('Locataires avec une location en cours')
                ->icon('heroicon-o-users')
                ->color('success')
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-success-50 to-white dark:from-success-900 dark:to-success-800 border-t-4 border-success-500 shadow-md rounded-lg',
                ]),

            Stat::make('💸 Impayés en cours', number_format(Unsold::sum('amount'), 0, ',', ' ') . ' FCFA')
                ->description('Montant total non réglé')
                ->icon('heroicon-o-exclamation-circle')
                ->color('danger')
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-danger-50 to-white dark:from-danger-900 dark:to-danger-800 border-t-4 border-danger-500 shadow-md rounded-lg',
                ]),

            Stat::make('Revenus du mois', number_format(
                Payment::whereMonth('date_payment', $now->month)
                    ->whereYear('date_payment', $now->year)
                    ->sum('amount'),
                0, ',', ' '
            ) . ' FCFA')
                ->description('Revenus du mois de ' . $currentMonthName)
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-success-50 to-white dark:from-success-900 dark:to-success-800 border-t-4 border-success-500 shadow-md rounded-lg',
                ]),

            Stat::make('Factures partiellement payées', Payment::where('status', 'partiel')->count())
                ->description('Nécessitent un suivi')
                ->icon('heroicon-o-document-check')
                ->color('warning')
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-warning-50 to-white dark:from-warning-900 dark:to-warning-800 border-t-4 border-warning-500 shadow-md rounded-lg',
                ]),

            Stat::make('⏳ Échéances à venir', Unsold::whereBetween('date', [$now, $now->copy()->addDays(7)])->count())
                ->description('Dans les 7 prochains jours')
                ->icon('heroicon-o-clock')
                ->color('info')
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-info-50 to-white dark:from-info-900 dark:to-info-800 border-t-4 border-info-500 shadow-md rounded-lg',
                ]),
        ];
    }
}