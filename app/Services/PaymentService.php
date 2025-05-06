<?php

namespace App\Services;

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
     *
     * @param int $month
     * @param int $year
     * @return Collection
     */
    public function getMonthlyPayments(int $month, int $year): Collection
    {
        return $this->paymentRepository->getMonthlyPayments($month, $year);
    }

    /**
     * Calcule les revenus totaux pour une période spécifique
     *
     * @param int $month
     * @param int $year
     * @return float
     */
    public function calculateMonthlyRevenue(int $month, int $year): float
    {
        return $this->paymentRepository->calculateMonthlyRevenue($month, $year);
    }

    /**
     * Calcule les commissions totales pour une propriété spécifique et une période donnée
     *
     * @param int $propertyId
     * @param int $month
     * @param int $year
     * @return float
     */
    public function calculateMonthlyCommissionsForProperty(int $propertyId, int $month, int $year): float
    {
        $flats = $this->flatRepository->getByPropertyId($propertyId);

        $totalCommissions = 0;

        foreach ($flats as $flat) {
            $payments = $this->getFlatPaymentsWithFlat($month, $year, $flat->id);


            $totalCommissions += $this->getTotalCommissionsForPayments($payments);
        }

        return $totalCommissions;
    }

    /**
 * Calcule le montant total à reverser au propriétaire d'une propriété après déduction des dépenses et commissions
 *
 * @param int $propertyId
 * @param int $month
 * @param int $year
 * @return float
 */
public function calculateAmountToTransferForProperty(int $propertyId, int $month, int $year): float
{
    // Récupérer tous les appartements de la propriété
    $flats = $this->flatRepository->getByPropertyId($propertyId);
    
    $totalRevenue = 0;
    $totalCommissions = 0;
    $totalFlatExpenses = 0;
    
    // Calculer les revenus et commissions pour chaque appartement
    foreach ($flats as $flat) {
        // Charger les paiements avec leurs flats en une seule requête
        $payments = $this->getFlatPaymentsWithFlat($month, $year, $flat->id);

        $totalRevenue += $payments->sum('amount');
        $totalCommissions += $this->getTotalCommissionsForPayments($payments);
        $totalFlatExpenses += $this->expenseRepository->calculateMonthlyExpenses($month, $year, $flat->id);
    }
    
    // Récupérer les dépenses spécifiques à la propriété (non associées à un appartement spécifique)
    $propertyExpenses = $this->expenseRepository->calculateMonthlyExpensesForProperty($propertyId, $month, $year);
    
    // Le montant total des dépenses est déjà inclus dans propertyExpenses (qui comprend les dépenses de tous les appartements)
    // Montant à reverser = Revenus - Commissions - Dépenses de la propriété
    return $totalRevenue - $totalCommissions - $propertyExpenses;
}

/**
 * Récupère les statistiques financières complètes pour une propriété
 *
 * @param int $propertyId
 * @param int $month
 * @param int $year
 * @return array
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
        // Charger les paiements avec leurs flats en une seule requête
        $payments = $this->getFlatPaymentsWithFlat($month, $year, $flat->id);

        
        $flatRevenue = $payments->sum('amount');
        $flatCommission = $this->getTotalCommissionsForPayments($payments);
        $flatExpenses = $this->expenseRepository->calculateMonthlyExpenses($month, $year, $flat->id);
        $flatAmountToTransfer = $flatRevenue - $flatCommission - $flatExpenses;
        
        $totalRevenue += $flatRevenue;
        $totalCommissions += $flatCommission;
        $totalFlatExpenses += $flatExpenses;
        
        $flatStats[$flat->id] = [
            'flat_id' => $flat->id,
            'flat_type' => $flat->type, // Utiliser le type au lieu du nom
            'revenue' => $flatRevenue,
            'commission' => $flatCommission,
            'expenses' => $flatExpenses,
            'amount_to_transfer' => $flatAmountToTransfer
        ];
    }
    
    // Récupérer les dépenses spécifiques à la propriété (non associées à un appartement spécifique)
    $propertyExpenses = $this->expenseRepository->calculateMonthlyExpensesForProperty($propertyId, $month, $year);
    
    // Le montant total des dépenses est déjà inclus dans propertyExpenses (qui comprend les dépenses de tous les appartements)
    // Montant à reverser = Revenus - Commissions - Dépenses de la propriété
    $amountToTransfer = $totalRevenue - $totalCommissions - $propertyExpenses;
    
    return [
        'property_id' => $propertyId,
        'month' => $month,
        'year' => $year,
        'total_revenue' => $totalRevenue,
        'total_commissions' => $totalCommissions,
        'total_flat_expenses' => $totalFlatExpenses,
        'property_specific_expenses' => $propertyExpenses - $totalFlatExpenses, // Dépenses spécifiques à la propriété sans les appartements
        'total_expenses' => $propertyExpenses, // propertyExpenses contient déjà toutes les dépenses
        'amount_to_transfer' => $amountToTransfer,
        'flat_stats' => $flatStats
    ];
}

    /**
     * Calcule les commissions totales pour une période spécifique
     *
     * @param int $month
     * @param int $year
     * @return float
     */
    public function calculateMonthlyCommissions(int $month, int $year): float
    {
        $totalCommissions = 0;

        // Récupérer tous les paiements du mois (payés et impayés)
        $payments = $this->paymentRepository->getMonthlyPayments($month, $year);

        foreach ($payments as $payment) {
            $flat = $this->flatRepository->findById($payment->flat_id);
            $totalCommissions += $this->flatRepository->calculateCommission($flat, $payment->amount);
        }

        return $totalCommissions;
    }

    /**
     * Calcule les commissions pour une période spécifique selon le statut de paiement
     *
     * @param int $month
     * @param int $year
     * @param int $status
     * @return float
     */
    public function calculateCommissionsByStatus(int $month, int $year, int $status): float
    {
        $totalCommissions = 0;

        // Récupérer les paiements selon le statut
        $payments = $this->paymentRepository->getMonthlyPaymentsByStatus($month, $year, $status);

        foreach ($payments as $payment) {
            $totalCommissions += $this->flatRepository->calculateCommission($payment->flat, $payment->amount);
        }

        return $totalCommissions;
    }

    /**
     * Calcule la commission pour un paiement spécifique
     *
     * @param Payment $payment
     * @return float
     */
    public function calculateCommissionForPayment(Payment $payment): float
    {
        if (!$payment->flat_id) {
            return 0;
        }

        $flat = $this->flatRepository->findById($payment->flat_id);

        if (!$flat) {
            return 0;
        }

        // Si c'est un montant fixe
        return $this->flatRepository->calculateCommission($flat, $payment->amount);
    }

    /**
     * Calcule les dépenses totales pour une propriété et une période spécifique
     *
     * @param int|null $flatId
     * @param int $month
     * @param int $year
     * @return float
     */
    public function calculateMonthlyExpenses(?int $flatId, int $month, int $year): float
    {
        return $this->expenseRepository->calculateMonthlyExpenses($month, $year, $flatId);
    }

    /**
     * Calcule les commissions totales pour un ensemble de paiements
     *
     * @param Collection $payments
     * @return float
     */
    private function getTotalCommissionsForPayments($payments): float
    {
        return $payments->sum(function ($payment) {
            if ($payment->flat) {
                return $this->flatRepository->calculateCommission($payment->flat, $payment->amount);
            }
            return 0;
        });
    }

    /**
     * Calcule le montant à reverser au propriétaire après déduction des dépenses et commissions
     *
     * @param int $flatId
     * @param int $month
     * @param int $year
     * @return float
     */
    public function calculateAmountToTransfer(int $flatId, int $month, int $year): float
    {
        // Vérifier si l'appartement existe
        $flat = $this->flatRepository->findById($flatId);
        if (!$flat) {
            return 0;
        }

        // Charger les paiements avec leurs flats en une seule requête
        $payments = $this->getFlatPaymentsWithFlat($month, $year, $flat->id);

        $totalRevenue = $payments->sum('amount');

        $totalCommissions = $this->getTotalCommissionsForPayments($payments);
        $totalExpenses = $this->expenseRepository->calculateMonthlyExpenses($month, $year, $flatId);

        // Montant à reverser = Revenus - Commissions - Dépenses
        return $totalRevenue - $totalCommissions - $totalExpenses;
    }

    /**
     * Obtient les statistiques globales des paiements
     *
     * @return array
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
     *
     * @param float $current
     * @param float $previous
     * @return float
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
     *
     * @param float $difference
     * @return string
     */
    public function formatPercentageDifference(float $difference): string
    {
        return $difference >= 0
            ? "+" . number_format($difference, 1) . "% par rapport au mois précédent"
            : number_format($difference, 1) . "% par rapport au mois précédent";
    }

    /**
     * Formate un montant pour l'affichage
     *
     * @param float $amount
     * @return string
     */
    public function formatAmount(float $amount): string
    {
        return number_format($amount, 0, ',', ' ') . ' FCFA';
    }

    private function getFlatPaymentsWithFlat(int $month, int $year, int $flatId)
{
    return $this->paymentRepository->getMonthlyPayments($month, $year, $flatId)->load('flat');
}

}