<?php

namespace App\Services;

use App\Enums\PaymentType;
use App\Models\Flat;
use App\Models\Payment;
use App\Models\Expense;
use App\Repositories\ExpenseRepository;
use App\Repositories\FlatRepository;
use App\Repositories\PaymentRepository;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;

class PaymentService
{
    protected $paymentRepository;
    protected $flatRepository;
    protected $expenseRepository;

    public function __construct(
        PaymentRepository $paymentRepository,
        FlatRepository $flatRepository,
        ExpenseRepository $expenseRepository
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->flatRepository = $flatRepository;
        $this->expenseRepository = $expenseRepository;
    }

    /**
     * Récupère les paiements pour un mois et une année spécifiques
     */
    public function getMonthlyPayments(int $month, int $year): Collection
    {
        return $this->paymentRepository->getMonthlyPayments($month, $year);
    }

    /**
     * Calcule les revenus totaux pour une période spécifique
     */
    public function calculateMonthlyRevenue(int $month, int $year): float
    {
        return $this->paymentRepository->calculateMonthlyRevenue($month, $year);
    }

    /**
     * Calcule les commissions totales pour une propriété spécifique et une période donnée
     * UNIQUEMENT sur les paiements de type LOYER
     */
    public function calculateMonthlyCommissionsForProperty(int $propertyId, int $month, int $year): float
    {
        $flats = $this->flatRepository->getByPropertyId($propertyId);
        $totalCommissions = 0;

        foreach ($flats as $flat) {
            // Récupérer TOUS les paiements du flat pour le mois/année
            $payments = $this->getFlatPayments($month, $year, $flat->id);
            
            // Calculer les commissions uniquement sur les loyers
            $totalCommissions += $this->getTotalCommissionsForPayments($payments);
        }

        return $totalCommissions;
    }

    /**
     * Calcule le montant total à reverser au propriétaire d'une propriété après déduction des dépenses et commissions
     */
    // public function calculateAmountToTransferForProperty(int $propertyId, int $month, int $year): float
    // {
    //     // Récupérer tous les appartements de la propriété
    //     $flats = $this->flatRepository->getByPropertyId($propertyId);
        
    //     $totalRevenue = 0;
    //     $totalCommissions = 0;
    //     $totalFlatExpenses = 0;
        
    //     // Calculer les revenus et commissions pour chaque appartement
    //     foreach ($flats as $flat) {
    //         // Récupérer TOUS les paiements (loyers + autres)
    //         $payments = $this->getFlatPayments($month, $year, $flat->id);
            
    //         // Revenus = TOUS les paiements
    //         $totalRevenue += $payments->sum('amount');
            
    //         // Commissions = UNIQUEMENT sur les loyers
    //         $totalCommissions += $this->getTotalCommissionsForPayments($payments);
            
    //         $totalFlatExpenses += $this->expenseRepository->calculateMonthlyExpenses($month, $year, $flat->id);
    //     }
        
    //     // Récupérer les dépenses spécifiques à la propriété
    //     $propertyExpenses = $this->expenseRepository->calculateMonthlyExpensesForProperty($propertyId, $month, $year);
        
    //     // Montant à reverser = Revenus (tous paiements) - Commissions (loyers uniquement) - Dépenses
    //     return $totalRevenue - $totalCommissions - $propertyExpenses;
    // }

    public function calculateAmountToTransferForProperty(int $propertyId, int $month, int $year, ?PaymentType $type = null): float
{
    $flats = $this->flatRepository->getByPropertyId($propertyId);
    $total = 0;

    foreach ($flats as $flat) {
        $payments = is_null($type)
            ? $this->paymentRepository->getMonthlyPaymentsByFlat($month, $year, $flat->id)
            : $this->paymentRepository->getMonthlyPaymentsByFlatAndType($month, $year, $flat->id, $type);

        $revenue = $payments->where('type', PaymentType::LOYER)->sum('amount');
        $commission = $type === PaymentType::LOYER ? $this->getTotalCommissionsForPayments($payments) : 0;
        $expenses = $this->expenseRepository->calculateMonthlyExpenses($month, $year, $flat->id);

        $total += ($revenue - $commission - $expenses);
    }

    return $total;
}

public function getTenantCautionAmount(?int $tenantId, int $propertyId): float
{
    if (is_null($tenantId)) {
        return 0;
    }

    return Payment::where('tenant_id', $tenantId)
        ->where('type', PaymentType::CAUTION->value)
        ->whereHas('flat', fn ($q) => $q->where('property_id', $propertyId))
        ->sum('amount');
}



    /**
     * Récupère les statistiques financières complètes pour une propriété
     */
    public function getPropertyFinancialStats(int $propertyId, int $month, int $year): array
    {
        // Récupérer tous les appartements de la propriété
        $flats = $this->flatRepository->getByPropertyId($propertyId);
        
        $totalRevenue = 0;
        $totalCommissions = 0;
        $totalFlatExpenses = 0;
        $flatStats = [];
        
        // Calculer les revenus et commissions pour chaque appartement
        foreach ($flats as $flat) {
            // Récupérer TOUS les paiements du flat
            $payments = $this->getFlatPayments($month, $year, $flat->id);
            
            // Revenus = TOUS les paiements
            $flatRevenue = $payments->sum('amount');
            
            // Commissions = UNIQUEMENT sur les loyers
            $flatCommission = $this->getTotalCommissionsForPayments($payments);
            
            $flatExpenses = $this->expenseRepository->calculateMonthlyExpenses($month, $year, $flat->id);
            $flatAmountToTransfer = $flatRevenue - $flatCommission - $flatExpenses;
            
            $totalRevenue += $flatRevenue;
            $totalCommissions += $flatCommission;
            $totalFlatExpenses += $flatExpenses;
            
            $flatStats[$flat->id] = [
                'flat_id' => $flat->id,
                'flat_type' => $flat->type,
                'revenue' => $flatRevenue,
                'commission' => $flatCommission,
                'expenses' => $flatExpenses,
                'amount_to_transfer' => $flatAmountToTransfer
            ];
        }
        
        // Récupérer les dépenses spécifiques à la propriété
        $propertyExpenses = $this->expenseRepository->calculateMonthlyExpensesForProperty($propertyId, $month, $year);
        
        // Montant à reverser = Revenus (tous paiements) - Commissions (loyers uniquement) - Dépenses
        $amountToTransfer = $totalRevenue - $totalCommissions - $propertyExpenses;
        
        return [
            'property_id' => $propertyId,
            'month' => $month,
            'year' => $year,
            'total_revenue' => $totalRevenue,
            'total_commissions' => $totalCommissions,
            'total_flat_expenses' => $totalFlatExpenses,
            'property_specific_expenses' => $propertyExpenses - $totalFlatExpenses,
            'total_expenses' => $propertyExpenses,
            'amount_to_transfer' => $amountToTransfer,
            'flat_stats' => $flatStats
        ];
    }

    /**
     * Calcule les commissions totales pour une période spécifique
     */
    public function calculateMonthlyCommissions(int $month, int $year): float
    {
        $totalCommissions = 0;

        // Récupérer UNIQUEMENT les paiements de type LOYER du mois
        $payments = $this->paymentRepository->getMonthlyPaymentsByType($month, $year, PaymentType::LOYER);

        foreach ($payments as $payment) {
            if ($payment->flat) {
                $totalCommissions += $this->flatRepository->calculateCommission($payment->flat, $payment->amount);
            }
        }

        return $totalCommissions;
    }

    /**
     * Calcule les commissions pour une période spécifique selon le statut de paiement
     * UNIQUEMENT sur les loyers
     */
    public function calculateCommissionsByStatus(int $month, int $year, int $status): float
    {
        $totalCommissions = 0;

        // Récupérer les paiements de type LOYER selon le statut
        $payments = $this->paymentRepository->getMonthlyLoyerPaymentsByStatus($month, $year, $status);

        foreach ($payments as $payment) {
            if ($payment->flat) {
                $totalCommissions += $this->flatRepository->calculateCommission($payment->flat, $payment->amount);
            }
        }

        return $totalCommissions;
    }

    /**
     * Calcule la commission pour un paiement spécifique
     * UNIQUEMENT si c'est un loyer
     */
    public function calculateCommissionForPayment(Payment $payment): float
    {
        if (!$payment->flat_id || $payment->type !== PaymentType::LOYER) {
            return 0;
        }

        $flat = $this->flatRepository->findById($payment->flat_id);

        if (!$flat) {
            return 0;
        }

        return $this->flatRepository->calculateCommission($flat, $payment->amount);
    }

    /**
     * Calcule les dépenses totales pour une propriété et une période spécifique
     */
    public function calculateMonthlyExpenses(?int $flatId, int $month, int $year): float
    {
        return $this->expenseRepository->calculateMonthlyExpenses($month, $year, $flatId);
    }

    /**
     * Calcule les commissions totales pour un ensemble de paiements
     * UNIQUEMENT sur les paiements de type LOYER
     */
    private function getTotalCommissionsForPayments($payments): float
    {
        return $payments->sum(function ($payment) {
            // Vérifier si le paiement a un flat ET si c'est un loyer
            if ($payment->flat && $payment->type === PaymentType::LOYER) {
                logger()->info('Calcul commission pour loyer:', [
                    'payment_id' => $payment->id,
                    'payment_type' => $payment->type->value,
                    'amount' => $payment->amount,
                    'flat_id' => $payment->flat
                ]);

                $commission = $this->flatRepository->calculateCommission($payment->flat, $payment->amount);

                logger()->info('Commission calculée:', [
                    'payment_id' => $payment->id,
                    'commission' => $commission
                ]);
                
                return $commission;
            }
            
            // Pas de commission pour les autres types de paiements
            if ($payment->type !== PaymentType::LOYER) {
                logger()->info('Pas de commission (pas un loyer):', [
                    'payment_id' => $payment->id,
                    'payment_type' => $payment->type->value ?? 'unknown'
                ]);
            }
            
            return 0;
        });
    }

    /**
     * Calcule le montant à reverser au propriétaire après déduction des dépenses et commissions
     */
    public function calculateAmountToTransfer(int $flatId, int $month, int $year): float
    {
        // Vérifier si l'appartement existe
        $flat = $this->flatRepository->findById($flatId);
        if (!$flat) {
            return 0;
        }

        // Récupérer TOUS les paiements du flat
        $payments = $this->getFlatPayments($month, $year, $flat->id);

        // Revenus = TOUS les paiements
        $totalRevenue = $payments->sum('amount');

        // Commissions = UNIQUEMENT sur les loyers
        $totalCommissions = $this->getTotalCommissionsForPayments($payments);
        
        $totalExpenses = $this->expenseRepository->calculateMonthlyExpenses($month, $year, $flatId);

        // Montant à reverser = Revenus (tous paiements) - Commissions (loyers uniquement) - Dépenses
        return $totalRevenue - $totalCommissions - $totalExpenses;
    }

    /**
     * Obtient les statistiques globales des paiements
     */
    public function getPaymentStats(): array
    {
        return [
            'total_payments' => $this->paymentRepository->getTotalCount(),
            'paid_payments' => $this->paymentRepository->getPaidCount(),
            'total_collected' => $this->paymentRepository->getTotalCollected(),
            'remaining_to_collect' => $this->paymentRepository->getRemainingToCollect(),
        ];
    }

    /**
     * Calcule le pourcentage de variation entre deux valeurs
     */
    public function calculatePercentageDifference(float $current, float $previous): float
    {
        if ($previous == 0) {
            return 0;
        }

        return (($current - $previous) / $previous) * 100;
    }

    /**
     * Formate une différence en pourcentage pour l'affichage
     */
    public function formatPercentageDifference(float $difference): string
    {
        return $difference >= 0
            ? "+" . number_format($difference, 1) . "% par rapport au mois précédent"
            : number_format($difference, 1) . "% par rapport au mois précédent";
    }

    /**
     * Formate un montant pour l'affichage
     */
    public function formatAmount(float $amount): string
    {
        return number_format($amount, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Récupère TOUS les paiements d'un flat pour un mois/année donnés
     * (pas seulement les loyers, pour le calcul des revenus totaux)
     */
    private function getFlatPayments(int $month, int $year, int $flatId)
    {
        return $this->paymentRepository->getMonthlyPaymentsByFlat($month, $year, $flatId)->load('flat');
    }

    /**
     * Calcul commission des loyers (méthode globale)
     */
    public function calculateTotalCommissionsFromRents(): float
    {
        $totalCommissions = 0;

        // Récupérer tous les appartements
        $flats = $this->flatRepository->getAll();

        foreach ($flats as $flat) {
            $loyer = $flat->loyer; // Montant du loyer
            $commissionValue = $flat->property_commission_value; // Valeur de la commission
            $commissionUnit = $flat->property_commission_unit; // Unité de la commission (% ou F CFA)

            // Calculer la commission en fonction de l'unité
            if ($commissionUnit === '%') {
                // Si la commission est en pourcentage
                $totalCommissions += ($loyer * $commissionValue) / 100;
            } elseif ($commissionUnit === 'F CFA') {
                // Si la commission est une valeur fixe
                $totalCommissions += $commissionValue;
            }
        }

        return $totalCommissions;
    }

    public function calculateLoyerTransferAmount(int $propertyId, int $month, int $year): float
{
    $flats = $this->flatRepository->getByPropertyId($propertyId);
    $total = 0;

    foreach ($flats as $flat) {
        $payments = $this->paymentRepository->getMonthlyPaymentsByFlatAndType($month, $year, $flat->id, PaymentType::LOYER);
        $revenue = $payments->sum('amount');
        $commissions = $this->getTotalCommissionsForPayments($payments);
        $expenses = $this->expenseRepository->calculateMonthlyExpenses($month, $year, $flat->id);

        $total += ($revenue - $commissions - $expenses);
    }

    return $total;
}

public function calculateCautionTransferAmount(int $propertyId): float
{
    $flats = $this->flatRepository->getByPropertyId($propertyId);
    $total = 0;

    foreach ($flats as $flat) {
        $payments = $this->paymentRepository->getPaymentsByFlatAndType($flat->id, PaymentType::CAUTION);

        $total += $payments->sum('amount');
    }

    return $total;
}


}