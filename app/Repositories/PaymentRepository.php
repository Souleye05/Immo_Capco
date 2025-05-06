<?php

namespace App\Repositories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class PaymentRepository
{
    /**
     * Récupère le nombre total de paiements
     *
     * @return int
     */
    public function getTotalCount(): int
    {
        return Payment::count();
    }

    /**
     * Récupère le nombre de paiements payés
     *
     * @return int
     */
    public function getPaidCount(): int
    {
        return Payment::where('status', 1)->count();
    }

    /**
     * Récupère le montant total encaissé
     *
     * @return float
     */
    public function getTotalCollected(): float
    {
        return Payment::where('status', 1)->sum('amount');
    }

    /**
     * Récupère le montant total restant à encaisser
     *
     * @return float
     */
    public function getRemainingToCollect(): float
    {
        return Payment::where('status', '!=', 1)->sum('amount');
    }

    /**
     * Récupère les paiements pour un mois et une année spécifiques
     *
     * @param int $month
     * @param int $year
     * @param int|null $flatId
     * @return Collection
     */
    public function getMonthlyPayments(int $month, int $year, ?int $flatId = null): Collection
    {
        $query = Payment::whereMonth('date_payment', $month)
            ->whereYear('date_payment', $year);
            
        if ($flatId) {
            $query->where('flat_id', $flatId);
        }
        
        return $query->get();
    }

    /**
     * Récupère les paiements pour un mois et une année spécifiques avec un statut spécifique
     *
     * @param int $month
     * @param int $year
     * @param int $status
     * @param int|null $flatId
     * @return Collection
     */
    public function getMonthlyPaymentsByStatus(int $month, int $year, int $status, ?int $flatId = null): Collection
    {
        $query = Payment::whereMonth('date_payment', $month)
            ->whereYear('date_payment', $year)
            ->where('status', $status);
            
        if ($flatId) {
            $query->where('flat_id', $flatId);
        }
        
        return $query->get();
    }

    /**
     * Calcule le revenu total pour un mois et une année spécifiques
     *
     * @param int $month
     * @param int $year
     * @param int|null $flatId
     * @return float
     */
    public function calculateMonthlyRevenue(int $month, int $year, ?int $flatId = null): float
    {
        $query = Payment::whereMonth('date_payment', $month)
            ->whereYear('date_payment', $year);
            
        if ($flatId) {
            $query->where('flat_id', $flatId);
        }
        
        return $query->sum('amount');
    }

    
}