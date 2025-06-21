<?php

namespace App\Repositories;

use App\Enums\PaymentType;
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
    public function getMonthlyPayments(int $month, int $year, ?int $flatId = null, ?PaymentType $type = null): Collection
    {
        $query = Payment::with('flatThroughContract')
            ->whereMonth('date_payment', $month)
            ->whereYear('date_payment', $year);
            
        if ($flatId !== null) {
            $query->where('flat_id', $flatId);
        }

        if ($type !== null) {
            $query->where('type', $type->value);
        }

        return $query->get();
    }

    public function getMonthlyLoyerPayments(int $month, int $year, ?int $flatId = null): Collection
{
    $query = Payment::with('flatThroughContract')
        ->whereMonth('date_payment', $month)
        ->whereYear('date_payment', $year)
        ->where('type', PaymentType::LOYER);

    if ($flatId !== null) {
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
            ->with('flatThroughContract')
            ->where('type', PaymentType::LOYER)
            ->where('status', $status);

        if ($flatId) {
            $query->where('flat_id', $flatId);
        }

        return $query->get();
    }

    public function getMonthlyPaymentsByFlat(int $month, int $year, int $flatId)
{
    return Payment::where('flat_id', $flatId)
        ->whereMonth('date_payment', $month)
        ->whereYear('date_payment', $year)
        ->get();
}

public function getMonthlyPaymentsByType(int $month, int $year, PaymentType $type)
{
    return Payment::whereMonth('date_payment', $month)
        ->whereYear('date_payment', $year)
        ->where('type', $type)
        ->with('flat')
        ->get();
}

public function getMonthlyLoyerPaymentsByStatus(int $month, int $year, int $status)
{
    return Payment::whereMonth('date_payment', $month)
        ->whereYear('date_payment', $year)
        ->where('type', PaymentType::LOYER)
        ->where('status', $status)
        ->with('flat')
        ->get();
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
public function getMonthlyPaymentsByFlatAndType(int $month, int $year, int $flatId, PaymentType $type)
{
    return Payment::whereMonth('date_payment', $month)
        ->whereYear('date_payment', $year)
        ->where('flat_id', $flatId)
        ->where('type', $type->value)
        ->get();
}

public function getPaymentsByFlatAndType(int $flatId, PaymentType $type)
{
    return Payment::where('flat_id', $flatId)
        ->where('type', $type->value)
        ->get();
}

    
}