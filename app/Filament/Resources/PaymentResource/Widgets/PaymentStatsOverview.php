<?php

namespace App\Filament\Resources\PaymentResource\Widgets;

use App\Models\Flat;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class PaymentStatsOverview extends BaseWidget
{
    protected function getStats(): array
{
    $now = Carbon::now();
    $currentMonth = $now->month;
    $currentYear = $now->year;

    // Total des factures
    $totalPayments = Payment::count();
    
    // Commissions du mois en cours
    $currentMonthCommissions = $this->calculateCurrentMonthCommissions($currentMonth, $currentYear);
    // Calcul de la différence des commissions
    $previousMonthCommissions = $this->calculateCurrentMonthCommissions(
        $currentMonth === 1 ? 12 : $currentMonth - 1,
        $currentMonth === 1 ? $currentYear - 1 : $currentYear
    );
    $commissionDifference = $previousMonthCommissions ? (($currentMonthCommissions - $previousMonthCommissions) / $previousMonthCommissions) * 100 : 0;
        $commissionDescription = $commissionDifference >= 0 
            ? "+" . number_format($commissionDifference, 1) . "% par rapport au mois précédent"
            : number_format($commissionDifference, 1) . "% par rapport au mois précédent";
    
    // Calcule des commissions payées et impayées du mois en cours
    $paidCommissions = $this->calculateCommissionsByStatus($currentMonth, $currentYear, 1);
    $unpaidCommissions = $currentMonthCommissions - $paidCommissions;


    // Factures payées
    $paidPayments = Payment::where('status', 1)->count();
    
    // Montant total encaissé (de tous les temps)
    $totalCollected = Payment::where('status', 1)->sum('amount');
    
    // Montant restant à encaisser
    $remainingToCollect = Payment::where('status', '!=', 1)->sum('amount');
        
    // Revenus du mois actuel
    $monthlyRevenue = Payment::whereMonth('date_payment', $currentMonth)
        ->whereYear('date_payment', $currentYear)
        ->sum('amount');
        
    $previousMonthRevenue = Payment::whereMonth('date_payment', $currentMonth === 1 ? 12 : $currentMonth - 1)
        ->whereYear('date_payment', $currentMonth === 1 ? $currentYear - 1 : $currentYear)
        ->sum('amount');
        
    $revenueDifference = $previousMonthRevenue ? (($monthlyRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100 : 0;
    $revenueDescription = $revenueDifference >= 0 
        ? "+" . number_format($revenueDifference, 1) . "% par rapport au mois précédent"
        : number_format($revenueDifference, 1) . "% par rapport au mois précédent";

    return [
        // Revenus du mois
        Stat::make('Revenus du mois', number_format($monthlyRevenue, 0, ',', ' ') . ' FCFA')
            ->description($revenueDescription)
            ->descriptionIcon($revenueDifference >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->icon('heroicon-o-banknotes')
            ->color($revenueDifference >= 0 ? 'success' : 'danger')
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-primary-50 to-white dark:from-primary-900 dark:to-primary-800 border-t-4 border-primary-500 shadow-md rounded-lg',
            ]),

        // Commissions du mois en cours
        Stat::make('Commissions du mois', number_format($currentMonthCommissions, 0, ',', ' ') . ' FCFA')
        ->description('Encaissées: ' . number_format($paidCommissions, 0, ',', ' ') . ' | À percevoir: ' . number_format($unpaidCommissions, 0, ',', ' '))
        ->descriptionIcon($commissionDifference >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
        ->icon('heroicon-o-currency-dollar')
        ->color($commissionDifference >= 0 ? 'success' : 'danger')
        ->extraAttributes([
            'class' => 'bg-gradient-to-br from-emerald-50 to-white dark:from-emerald-900 dark:to-emerald-800 border-t-4 border-emerald-500 shadow-md rounded-lg',
        ]),

        // Factures payées / total
        Stat::make('Factures payées', $paidPayments . '/' . $totalPayments)
            ->description('Reste: ' . ($totalPayments - $paidPayments) . ' factures')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-success-50 to-white dark:from-success-900 dark:to-success-800 border-t-4 border-success-500 shadow-md rounded-lg',
            ]),

        // Montant total encaissé
        Stat::make('Montant encaissé', number_format($totalCollected, 0, ',', ' ') . ' FCFA')
            ->description('Total des paiements reçus')
            ->icon('heroicon-o-currency-dollar')
            ->color('primary')
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-info-50 to-white dark:from-info-900 dark:to-info-800 border-t-4 border-info-500 shadow-md rounded-lg',
            ]),

        // Montant restant à encaisser
        Stat::make('Montant à encaisser', number_format($remainingToCollect, 0, ',', ' ') . ' FCFA')
            ->description('Paiements en attente')
            ->icon('heroicon-o-clock')
            ->color('warning')
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-warning-50 to-white dark:from-warning-900 dark:to-warning-800 border-t-4 border-warning-500 shadow-md rounded-lg',
            ]),
    ];
}
    
    /**
     * Calcule les commissions totales pour un mois spécifique
     */
    protected function calculateCurrentMonthCommissions(int $month, int $year): float
    {
        $totalCommissions = 0;
        
        // Récupérer tous les paiements du mois en cours (payés et impayés)
        $payments = Payment::whereMonth('date_payment', $month)
            ->whereYear('date_payment', $year)
            ->get();
            
        foreach ($payments as $payment) {
            // Si le paiement est lié à un appartement, calculer la commission
            if ($payment->flat_id) {
                $flat = Flat::find($payment->flat_id);
                
                if ($flat) {
                    // Calculer la commission selon l'unité (pourcentage ou montant fixe)
                    if ($flat->property_commission_unit === 'percentage') {
                        $commission = ($payment->amount * $flat->property_commission_value) / 100;
                    } else {
                        // Si c'est un montant fixe
                        $commission = $flat->property_commission_value;
                    }
                    
                    $totalCommissions += $commission;
                }
            }
        }
        
        return $totalCommissions;
    }
    
    /**
     * Calcule les commissions pour un mois spécifique selon le statut de paiement
     */
    protected function calculateCommissionsByStatus(int $month, int $year, int $status): float
    {
        $totalCommissions = 0;
        
        // Récupérer les paiements selon le statut
        $payments = Payment::whereMonth('date_payment', $month)
            ->whereYear('date_payment', $year)
            ->where('status', $status)
            ->get();
            
        foreach ($payments as $payment) {
            if ($payment->flat_id) {
                $flat = Flat::find($payment->flat_id);
                
                if ($flat) {
                    // Calculer la commission selon l'unité
                    if ($flat->property_commission_unit === 'percentage') {
                        $commission = ($payment->amount * $flat->property_commission_value) / 100;
                    } else {
                        $commission = $flat->property_commission_value;
                    }
                    
                    $totalCommissions += $commission;
                }
            }
        }
        
        return $totalCommissions;
    }
}
