<?php

    namespace App\Filament\Resources\ExpenseResource\Widgets;

    use App\Models\Expense;
    use Filament\Widgets\StatsOverviewWidget as BaseWidget;
    use Filament\Widgets\StatsOverviewWidget\Stat;
    use Illuminate\Support\Carbon;

    class ExpenseStatsOverview extends BaseWidget
    {
        protected function getStats(): array
        {
            $now = Carbon::now();
            $currentMonth = $now->month;
            $currentYear = $now->year;
            $currentMonthName = $now->locale('fr')->isoFormat('MMMM');

            // // Total des dépenses
            $totalExpenses = Expense::sum('amount');

            // // Total des depenses du mois courant
            $currentMonthExpenses = Expense::whereMonth('payment_date', $currentMonth)
                ->whereYear('payment_date', $currentYear)
                ->sum('amount');

            // // Total des depenses du mois precedent
            $previousMonthExpenses = Expense::whereMonth('payment_date', $currentMonth === 1 ? 12 : $currentMonth - 1)
                ->whereYear('payment_date', $currentMonth === 1 ? $currentYear - 1 : $currentYear)
                ->sum('amount');

                //  Calculer le pourcentage de variation par rapport au mois precedent
                $expenseDifference = $previousMonthExpenses ? (($currentMonthExpenses - $previousMonthExpenses) / $previousMonthExpenses) * 100 : 0;
                $expenseDescription = $expenseDifference >= 0 
                    ? "+" . number_format($expenseDifference, 1) . "% par rapport au mois précédent"
                    : number_format($expenseDifference, 1) . "% par rapport au mois précedent";

                // Dépenses par catégories (top catégorie)
            $topType = Expense::selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->orderByRaw('SUM(amount) DESC')
            ->first();
            
        // Moyenne mensuelle des dépenses sur l'année en cours
        $yearlyAverage = Expense::whereYear('payment_date', $currentYear)
            ->selectRaw('AVG(amount) as average_amount')
            ->first()->average_amount ?? 0;
                
            return [
                //
                Stat::make('Total des depenses', $totalExpenses)
                ->description('Nombre total de dépenses enregistrées')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('gray')
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-900 border-t-4 border-gray-500 shadow-md rounded-lg',
                    ]),
                
                    Stat::make('Dépenses ' . $currentMonthName, number_format($currentMonthExpenses, 0, ',', ' ') . ' FCFA')
                    ->description($expenseDescription)
                    ->descriptionIcon($expenseDifference <= 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                    ->icon('heroicon-o-banknotes')
                    ->color($expenseDifference <= 0 ? 'success' : 'danger')
                    ->extraAttributes([
                        'class' => 'bg-gradient-to-br from-primary-50 to-white dark:from-primary-900 dark:to-primary-800 border-t-4 border-primary-500 shadow-md rounded-lg',
                    ]),

                    Stat::make('Catégorie principale', $topType ? $topType->category : 'Aucune')
                    ->description($topType ? number_format($topType->total, 0, ',', ' ') . ' FCFA' : 'Pas de données')
                    ->icon('heroicon-o-chart-pie')
                    ->color('warning')
                    ->extraAttributes([
                        'class' => 'bg-gradient-to-br from-warning-50 to-white dark:from-warning-900 dark:to-warning-800 border-t-4 border-warning-500 shadow-md rounded-lg',
                    ]),

                Stat::make('Moyenne mensuelle', number_format($yearlyAverage, 0, ',', ' ') . ' FCFA')
                    ->description('Pour l\'année ' . $currentYear)
                    ->icon('heroicon-o-calculator')
                    ->color('info')
                    ->extraAttributes([
                        'class' => 'bg-gradient-to-br from-info-50 to-white dark:from-info-900 dark:to-info-800 border-t-4 border-info-500 shadow-md rounded-lg',
                    ]),

                
                ];
        }
    }
