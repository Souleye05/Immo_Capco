<?php

namespace App\Filament\Resources\ExpenseResource\Widgets;

use App\Models\Expense;
use App\Models\Property;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ExpenseStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $now = Carbon::now();
        $currentMonth = $now->month;
        $currentYear = $now->year;
        $currentMonthName = $now->locale('fr')->isoFormat('MMMM');
        $currentDay = $now->day;
        $daysInMonth = $now->daysInMonth;

        // Total des dépenses
        $totalExpenses = Expense::sum('amount');

        // Total des dépenses du mois courant
        $currentMonthExpenses = Expense::whereMonth('payment_date', $currentMonth)
            ->whereYear('payment_date', $currentYear)
            ->sum('amount');

        // Total des dépenses du mois précédent
        $previousMonthExpenses = Expense::whereMonth('payment_date', $currentMonth === 1 ? 12 : $currentMonth - 1)
            ->whereYear('payment_date', $currentMonth === 1 ? $currentYear - 1 : $currentYear)
            ->sum('amount');

        // Calculer le pourcentage de variation par rapport au mois précédent
        $expenseDifference = $previousMonthExpenses ? (($currentMonthExpenses - $previousMonthExpenses) / $previousMonthExpenses) * 100 : 0;
        $expenseDescription = $expenseDifference >= 0 
            ? "+" . number_format($expenseDifference, 1) . "% par rapport au mois précédent"
            : number_format($expenseDifference, 1) . "% par rapport au mois précédent";

        // Dépenses par catégories (top catégorie)
        $topType = Expense::selectRaw('categorie_depense_id, SUM(amount) as total')
            ->with('categorie')
            ->groupBy('categorie_depense_id')
            ->orderByRaw('SUM(amount) DESC')
            ->first();
        
        // Propriété avec le plus de dépenses
        $topProperty = Expense::selectRaw('property_id, SUM(amount) as total')
            ->with('property')
            ->groupBy('property_id')
            ->orderByRaw('SUM(amount) DESC')
            ->first();
            
        // Moyenne mensuelle des dépenses sur l'année en cours
        $yearlyAverage = Expense::whereYear('payment_date', $currentYear)
            ->selectRaw('AVG(amount) as average_amount')
            ->first()->average_amount ?? 0;
            
        // Tendance trimestrielle (trimestre actuel vs précédent)
        $currentQuarter = ceil($currentMonth / 3);
        $currentQuarterStart = Carbon::create($currentYear, ($currentQuarter - 1) * 3 + 1, 1);
        $currentQuarterEnd = $currentQuarterStart->copy()->addMonths(3)->subDay();
        
        $previousQuarterStart = $currentQuarterStart->copy()->subMonths(3);
        $previousQuarterEnd = $currentQuarterStart->copy()->subDay();
        
        $currentQuarterExpenses = Expense::whereBetween('payment_date', [$currentQuarterStart, $currentQuarterEnd])
            ->sum('amount');
            
        $previousQuarterExpenses = Expense::whereBetween('payment_date', [$previousQuarterStart, $previousQuarterEnd])
            ->sum('amount');
            
        $quarterDifference = $previousQuarterExpenses ? (($currentQuarterExpenses - $previousQuarterExpenses) / $previousQuarterExpenses) * 100 : 0;
        
        // Dernière dépense importante (> moyenne mensuelle)
        $lastImportantExpense = Expense::where('amount', '>', $yearlyAverage)
            ->with(['categorie', 'property'])
            ->orderBy('payment_date', 'desc')
            ->first();
            
        // Formater la date de paiement pour la dernière dépense importante
        $lastExpenseDate = $lastImportantExpense ? 
            (is_string($lastImportantExpense->payment_date) ? 
                Carbon::parse($lastImportantExpense->payment_date)->format('d/m/Y') : 
                $lastImportantExpense->payment_date->format('d/m/Y')
            ) : '';
            
        // NOUVELLES FONCTIONNALITÉS
        
        // 1. PRÉDICTION AUTOMATIQUE DES DÉPENSES DU MOIS
        // Calculer la moyenne quotidienne du mois en cours
        $dailyAverage = $currentDay > 0 ? $currentMonthExpenses / $currentDay : 0;
        // Prédire le total du mois en fonction de la moyenne quotidienne
        $predictedMonthTotal = $dailyAverage * $daysInMonth;
        // Calculer le pourcentage de progression
        $monthProgressPercent = ($currentDay / $daysInMonth) * 100;
        
        // 2. TOP 3 DES PROPRIÉTÉS LES PLUS COÛTEUSES
        $topProperties = Expense::selectRaw('property_id, SUM(amount) as total')
            ->with('property')
            ->groupBy('property_id')
            ->orderByRaw('SUM(amount) DESC')
            ->limit(3)
            ->get();
            
        $topPropertiesText = '';
        foreach ($topProperties as $index => $prop) {
            if (isset($prop->property)) {
                $topPropertiesText .= ($index + 1) . '. ' . $prop->property->full_name . ' (' . 
                    number_format($prop->total, 0, ',', ' ') . ' FCFA)';
                if ($index < count($topProperties) - 1) {
                    $topPropertiesText .= "\n";
                }
            }
        }
        
        if (empty($topPropertiesText)) {
            $topPropertiesText = 'Aucune donnée disponible';
        }
        
        // 3. ÉVOLUTION ANNUELLE (DÉPENSES CUMULÉES PAR MOIS)
        $monthlyCumulative = [];
        $yearTotal = 0;
        
        for ($month = 1; $month <= 12; $month++) {
            if ($month > $currentMonth && $now->year == $currentYear) {
                break; // Ne pas inclure les mois futurs
            }
            
            $monthlyAmount = Expense::whereMonth('payment_date', $month)
                ->whereYear('payment_date', $currentYear)
                ->sum('amount');
                
            $yearTotal += $monthlyAmount;
            $monthlyCumulative[$month] = $yearTotal;
        }
        
        // Calculer la tendance annuelle (pourcentage d'augmentation mois par mois)
        $annualTrend = 0;
        $lastMonthCumulative = 0;
        $monthCount = count($monthlyCumulative);
        
        if ($monthCount >= 2) {
            $firstMonth = array_key_first($monthlyCumulative);
            $lastMonth = array_key_last($monthlyCumulative);
            
            if ($monthlyCumulative[$firstMonth] > 0) {
                $annualTrend = (($monthlyCumulative[$lastMonth] - $monthlyCumulative[$firstMonth]) / $monthlyCumulative[$firstMonth]) * 100;
            }
            
            // Pour la description, comparer avec le mois précédent
            if ($lastMonth > 1 && isset($monthlyCumulative[$lastMonth - 1])) {
                $lastMonthCumulative = $monthlyCumulative[$lastMonth] - $monthlyCumulative[$lastMonth - 1];
            } else {
                $lastMonthCumulative = $monthlyCumulative[$lastMonth];
            }
        }

        return [
            // Total des dépenses
            Stat::make('Total des dépenses', number_format($totalExpenses, 0, ',', ' ') . ' FCFA')
                ->description('Tous les paiements enregistrés')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('gray')
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-900 border-t-4 border-gray-500 shadow-md rounded-lg',
                ]),
            
            // Dépenses du mois courant
            Stat::make('Dépenses ' . $currentMonthName, number_format($currentMonthExpenses, 0, ',', ' ') . ' FCFA')
                ->description($expenseDescription)
                ->descriptionIcon($expenseDifference <= 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->descriptionColor($expenseDifference <= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-banknotes')
                ->color('primary')
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-primary-50 to-white dark:from-primary-900 dark:to-primary-800 border-t-4 border-primary-500 shadow-md rounded-lg',
                ]),

            // PRÉDICTION MENSUELLE
            Stat::make('Prédiction ' . $currentMonthName, number_format($predictedMonthTotal, 0, ',', ' ') . ' FCFA')
                ->description('Progression: ' . number_format($monthProgressPercent, 1) . '% du mois écoulé')
                ->descriptionIcon('heroicon-o-clock')
                ->icon('heroicon-o-light-bulb')
                ->color('success')
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-success-50 to-white dark:from-success-900 dark:to-success-800 border-t-4 border-success-500 shadow-md rounded-lg',
                ]),

            // TOP 3 PROPRIÉTÉS COÛTEUSES
            Stat::make('Top propriétés coûteuses', 'Top 3')
                ->description($topPropertiesText)
                ->icon('heroicon-o-building-office-2')
                ->color('danger')
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-danger-50 to-white dark:from-danger-900 dark:to-danger-800 border-t-4 border-danger-500 shadow-md rounded-lg',
                ]),

            // ÉVOLUTION ANNUELLE
            Stat::make('Cumul annuel ' . $currentYear, number_format(end($monthlyCumulative), 0, ',', ' ') . ' FCFA')
                ->description('Dernier mois: ' . number_format($lastMonthCumulative, 0, ',', ' ') . ' FCFA')
                ->descriptionIcon($annualTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->descriptionColor($annualTrend >= 0 ? 'danger' : 'success')
                ->icon('heroicon-o-presentation-chart-line')
                ->chart(array_values($monthlyCumulative))
                ->color('warning')
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-warning-50 to-white dark:from-warning-900 dark:to-warning-800 border-t-4 border-warning-500 shadow-md rounded-lg',
                ]),

            // Catégorie principale
            Stat::make('Catégorie principale', $topType && isset($topType->categorie) ? $topType->categorie->categorie : 'Aucune')
                ->description($topType ? number_format($topType->total, 0, ',', ' ') . ' FCFA' : 'Pas de données')
                ->icon('heroicon-o-chart-pie')
                ->color('info')
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-info-50 to-white dark:from-info-900 dark:to-info-800 border-t-4 border-info-500 shadow-md rounded-lg',
                ]),

            // Moyenne mensuelle
            Stat::make('Moyenne mensuelle', number_format($yearlyAverage, 0, ',', ' ') . ' FCFA')
                ->description('Pour l\'année ' . $currentYear)
                ->icon('heroicon-o-calculator')
                ->color('secondary')
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-secondary-50 to-white dark:from-secondary-900 dark:to-secondary-800 border-t-4 border-secondary-500 shadow-md rounded-lg',
                ]),
        ];
    }
    
    protected static ?string $pollingInterval = '15m'; // Rafraîchir automatiquement toutes les 15 minutes
    
    protected int | string | array $columnSpan = 'full'; // Prend toute la largeur
}