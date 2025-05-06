<?php

namespace App\Repositories;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Collection;

class ExpenseRepository
{
    /**
     * Récupère le nombre total de dépenses
     *
     * @return int
     */
    public function getTotalExpenses(): float
    {
        return Expense::sum('amount');
    }

    /**
     * Calcule le montant total des dépenses pour un mois et une année spécifiques
     *
     * @param int $month
     * @param int $year
     * @param int|null $flatId
     * @return float
     */
    public function calculateMonthlyExpenses(int $month, int $year, ?int $flatId = null): float
    {
        $query = Expense::whereMonth('payment_date', $month)
            ->whereYear('payment_date', $year);
            
        if ($flatId) {
            $query->where('flat_id', $flatId);
        }
        
        return $query->sum('amount');
    }

    

    /**
     * Récupère les dépenses pour un appartement spécifique
     *
     * @param int $flatId
     * @return Collection
     */
    public function getByFlatId(int $flatId): Collection
    {
        return Expense::where('flat_id', $flatId)->get();
    }

    /**
     * Récupère le total des dépenses pour un appartement spécifique
     *
     * @param int $flatId
     * @return float
     */
    public function getTotalByFlatId(int $flatId): float
    {
        return Expense::where('flat_id', $flatId)->sum('amount');
    }
    /**
     * Récupère les dépenses pour une propriété spécifique
     *
     * @param int $propertyId
     * @return Collection
     */
    public function getByPropertyId(int $propertyId): Collection
    {
        return Expense::where('property_id', $propertyId)->get();
    }
    /**
     * Récupère le total des dépenses pour une propriété spécifique
     *
     * @param int $propertyId
     * @return float
     */
    public function getTotalByPropertyId(int $propertyId): float
    {
        return Expense::where('property_id', $propertyId)->sum('amount');
    }

    /**
 * Calcule les dépenses totales pour une propriété spécifique et une période donnée
 *
 * @param int $propertyId
 * @param int $month
 * @param int $year
 * @return float
 */
public function calculateMonthlyExpensesForProperty(int $propertyId, int $month, int $year): float
{
    $query = Expense::where('property_id', $propertyId)
        ->whereMonth('payment_date', $month)
        ->whereYear('payment_date', $year);

    return $query->sum('amount');
}

    // Recupère les dépenses pour une période spécifique
    public function getExpensesForPeriod($startDate, $endDate): Collection
    {
        return Expense::whereBetween('payment_date', [$startDate, $endDate])
        ->sum('amount');
    }

    /**
     * Récupère les top N propriétés avec le plus de dépenses
     */
    public function getTopProperties(int $limit = 3): Collection
    {
        return Expense::selectRaw('property_id, SUM(amount) as total')
            ->with('property')
            ->groupBy('property_id')
            ->orderByRaw('SUM(amount) DESC')
            ->limit($limit)
            ->get();
    }
    /**
     * Récupère la catégorie principale des dépenses
     */
    public function getTopCategory()
    {
        return Expense::selectRaw('categorie_depense_id, SUM(amount) as total')
            ->with('categorie')
            ->groupBy('categorie_depense_id')
            ->orderByRaw('SUM(amount) DESC')
            ->first();
    }
    
         
}