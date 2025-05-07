<?php

namespace App\Services;

use App\Models\Expense;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use App\Repositories\ExpenseRepository;
use App\Repositories\FlatRepository;
use App\Repositories\PaymentRepository;

class ExpenseService
{
  protected $paymentRepository ;
    protected$flatRepository;
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

  
    //  * Total des dépenses
    
    public function totalExpenses(): float
    {
        return $this->expenseRepository->getTotalExpenses();
    }
    // Calcule les dépenses totales du mois courant
    public function calculateMonthlyExpenses(int $month, int $year, ?int $flatId = null): float
    {
        return $this->expenseRepository->calculateMonthlyExpenses($month, $year, $flatId);
    }

    // Récupérer les top N propriétés avec le plus de dépenses
    public function getTopNPropertiesWithMostExpenses(int $limit = 3): string
    {
        $topProperties = $this->expenseRepository->getTopProperties ($limit);

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
    
    return empty($topPropertiesText) ? 'Aucune donnée disponible' : $topPropertiesText;
}
     
    // Récupère les informations sur la catégorie principale des dépenses
    public function getTopCategoryInfo(): array
    {
        $topType = $this->expenseRepository->getTopCategory();

        return [
            'name' => $topType && isset($topType->categorie) ? $topType->categorie->categorie : 'Aucune donnée disponible',
            'total' => $topType ? (float) $topType->total : 0, // Vérification si $topType est null
        ];
    } 

}