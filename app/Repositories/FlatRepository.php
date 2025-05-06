<?php

namespace App\Repositories;

use App\Models\Flat;
use Illuminate\Database\Eloquent\Collection;

class FlatRepository
{
    /**
     * Récupère un appartement par son ID
     *
     * @param int $flatId
     * @return Flat|null
     */
    public function findById(int $flat_id): ?Flat
    {
        return Flat::where('id', $flat_id)->first();
    }

    /**
     * Récupère tous les appartements
     *
     * @return Collection
     */
    public function getAll(): Collection
    {
        return Flat::all();
    }

    /**
     * Récupère les appartements d'une propriété spécifique
     *
     * @param int $propertyId
     * @return Collection
     */
    public function getByPropertyId(int $propertyId): Collection
    {
        return Flat::where('property_id', $propertyId)->get();
    }
    /**
     * Récupère les appartements d'un propriétaire spécifique
     *
     * @param int $ownerId
     * @return Collection
     */
    public function getByOwnerId(int $ownerId): Collection
    {
        return Flat::where('property_id', $ownerId)->get();
    }

    /**
     * Calcule la commission pour un appartement en fonction d'un montant
     * Combine commission fixe et pourcentage
     *
     * @param Flat $flat
     * @param float $amount
     * @return float
     */
    public function calculateCommission(Flat $flat, float $amount): float
{
    $commission = 0;
    
    // Récupération de la valeur et de l'unité de commission
    $value = $flat->property_commission_value;
    $unit = $flat->property_commission_unit;
    
    // Calcul de la commission selon l'unité
    if ($unit === '%') {
        // Si l'unité est en pourcentage, on calcule un pourcentage du montant
        $commission = ($amount * $value) / 100;
    } elseif ($unit === 'F CFA') {
        // Si l'unité est en F CFA, on prend directement la valeur
        $commission = $value;
    }
    
    return $commission;
}

    /**
     * Récupère le montant total des commissions pour un propriétaire spécifique
     * 
     * @param Collection $payments
     * @return float
     */
    public function getTotalCommissionsForPayments(Collection $payments): float
    {
        $totalCommissions = 0;
        
        foreach ($payments as $payment) {
            $totalCommissions += $this->calculateCommission($payment->flat, $payment->amount);
        }
        
        return $totalCommissions;
    }
}