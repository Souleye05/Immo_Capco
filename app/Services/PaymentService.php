<?php

namespace App\Services;

use App\Enums\PaymentType;
use App\Models\Flat;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Expense;
use App\Repositories\ExpenseRepository;
use App\Repositories\FlatRepository;
use App\Repositories\PaymentRepository;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

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
     * Validates that a property ID is not null and exists in the database
     *
     * @param int|null $propertyId
     * @throws InvalidArgumentException
     */
    private function validatePropertyId(?int $propertyId): void
    {
        if ($propertyId === null) {
            Log::error('PaymentService validation failed: Property ID cannot be null', [
                'method' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown',
                'property_id' => $propertyId
            ]);
            throw new InvalidArgumentException('Property ID cannot be null for remittance calculations');
        }

        if ($propertyId <= 0) {
            Log::error('PaymentService validation failed: Property ID must be positive', [
                'method' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown',
                'property_id' => $propertyId
            ]);
            throw new InvalidArgumentException('Property ID must be a positive integer');
        }

        if (!Property::find($propertyId)) {
            Log::error('PaymentService validation failed: Property not found', [
                'method' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown',
                'property_id' => $propertyId
            ]);
            throw new InvalidArgumentException("Property with ID {$propertyId} not found");
        }
    }

    /**
     * Validates month and year parameters
     *
     * @param int $month
     * @param int $year
     * @throws InvalidArgumentException
     */
    private function validateMonthYear(int $month, int $year): void
    {
        if ($month < 1 || $month > 12) {
            Log::error('PaymentService validation failed: Invalid month', [
                'method' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown',
                'month' => $month,
                'year' => $year
            ]);
            throw new InvalidArgumentException('Month must be between 1 and 12');
        }

        if ($year < 1900 || $year > 2100) {
            Log::error('PaymentService validation failed: Invalid year', [
                'method' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown',
                'month' => $month,
                'year' => $year
            ]);
            throw new InvalidArgumentException('Year must be between 1900 and 2100');
        }
    }

    /**
     * Validates that a flat ID is not null and exists
     *
     * @param int|null $flatId
     * @throws InvalidArgumentException
     */
    private function validateFlatId(?int $flatId): void
    {
        if ($flatId === null) {
            Log::error('PaymentService validation failed: Flat ID cannot be null', [
                'method' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown',
                'flat_id' => $flatId
            ]);
            throw new InvalidArgumentException('Flat ID cannot be null');
        }

        if ($flatId <= 0) {
            Log::error('PaymentService validation failed: Flat ID must be positive', [
                'method' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown',
                'flat_id' => $flatId
            ]);
            throw new InvalidArgumentException('Flat ID must be a positive integer');
        }

        if (!$this->flatRepository->findById($flatId)) {
            Log::error('PaymentService validation failed: Flat not found', [
                'method' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown',
                'flat_id' => $flatId
            ]);
            throw new InvalidArgumentException("Flat with ID {$flatId} not found");
        }
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
    public function calculateMonthlyCommissionsForProperty(?int $propertyId, int $month, int $year): float
    {
        try {
            // Validate input parameters
            $this->validatePropertyId($propertyId);
            $this->validateMonthYear($month, $year);

            Log::info('PaymentService: Starting monthly commissions calculation for property', [
                'property_id' => $propertyId,
                'month' => $month,
                'year' => $year
            ]);

            $flats = $this->flatRepository->getByPropertyId($propertyId);

            if ($flats->isEmpty()) {
                Log::warning('PaymentService: No flats found for property', [
                    'property_id' => $propertyId
                ]);
                return 0;
            }

            $totalCommissions = 0;

            foreach ($flats as $flat) {
                // Récupérer TOUS les paiements du flat pour le mois/année
                $payments = $this->getFlatPayments($month, $year, $flat->id);

                // Calculer les commissions uniquement sur les loyers
                $flatCommissions = $this->getTotalCommissionsForPayments($payments);
                $totalCommissions += $flatCommissions;

                Log::debug('PaymentService: Flat commissions calculated', [
                    'flat_id' => $flat->id,
                    'commissions' => $flatCommissions
                ]);
            }

            Log::info('PaymentService: Monthly commissions calculation completed', [
                'property_id' => $propertyId,
                'total_commissions' => $totalCommissions
            ]);

            return $totalCommissions;
        } catch (InvalidArgumentException $e) {
            Log::error('PaymentService: Validation error in calculateMonthlyCommissionsForProperty', [
                'property_id' => $propertyId,
                'month' => $month,
                'year' => $year,
                'error' => $e->getMessage()
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('PaymentService: Unexpected error in calculateMonthlyCommissionsForProperty', [
                'property_id' => $propertyId,
                'month' => $month,
                'year' => $year,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Failed to calculate monthly commissions for property: ' . $e->getMessage(), 0, $e);
        }
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

    public function calculateAmountToTransferForProperty(?int $propertyId, int $month, int $year, ?PaymentType $type = null): float
    {
        try {
            // Validate input parameters
            $this->validatePropertyId($propertyId);
            $this->validateMonthYear($month, $year);

            Log::info('PaymentService: Starting amount to transfer calculation for property', [
                'property_id' => $propertyId,
                'month' => $month,
                'year' => $year,
                'type' => $type?->value
            ]);

            $flats = $this->flatRepository->getByPropertyId($propertyId);

            if ($flats->isEmpty()) {
                Log::warning('PaymentService: No flats found for property', [
                    'property_id' => $propertyId
                ]);
                return 0;
            }

            $total = 0;

            foreach ($flats as $flat) {
                $payments = is_null($type)
                    ? $this->paymentRepository->getMonthlyPaymentsByFlat($month, $year, $flat->id)
                    : $this->paymentRepository->getMonthlyPaymentsByFlatAndType($month, $year, $flat->id, $type);

                $revenue = $payments->where('type', PaymentType::LOYER)->sum('amount');
                $commission = $type === PaymentType::LOYER ? $this->getTotalCommissionsForPayments($payments) : 0;
                $expenses = $this->expenseRepository->calculateMonthlyExpenses($month, $year, $flat->id);

                $flatTotal = $revenue - $commission - $expenses;
                $total += $flatTotal;

                Log::debug('PaymentService: Flat transfer calculation completed', [
                    'flat_id' => $flat->id,
                    'revenue' => $revenue,
                    'commission' => $commission,
                    'expenses' => $expenses,
                    'flat_total' => $flatTotal
                ]);
            }

            Log::info('PaymentService: Amount to transfer calculation completed', [
                'property_id' => $propertyId,
                'total_amount' => $total
            ]);

            return $total;
        } catch (InvalidArgumentException $e) {
            Log::error('PaymentService: Validation error in calculateAmountToTransferForProperty', [
                'property_id' => $propertyId,
                'month' => $month,
                'year' => $year,
                'type' => $type?->value,
                'error' => $e->getMessage()
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('PaymentService: Unexpected error in calculateAmountToTransferForProperty', [
                'property_id' => $propertyId,
                'month' => $month,
                'year' => $year,
                'type' => $type?->value,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Failed to calculate amount to transfer for property: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getTenantCautionAmount(?int $tenantId, ?int $propertyId): float
    {
        try {
            // Validate property ID
            $this->validatePropertyId($propertyId);

            Log::info('PaymentService: Starting tenant caution amount calculation', [
                'tenant_id' => $tenantId,
                'property_id' => $propertyId
            ]);

            if (is_null($tenantId)) {
                Log::info('PaymentService: Tenant ID is null, returning 0', [
                    'property_id' => $propertyId
                ]);
                return 0;
            }

            if ($tenantId <= 0) {
                Log::warning('PaymentService: Invalid tenant ID provided', [
                    'tenant_id' => $tenantId,
                    'property_id' => $propertyId
                ]);
                return 0;
            }

            $amount = Payment::where('tenant_id', $tenantId)
                ->where('type', PaymentType::CAUTION->value)
                ->whereHas('flat', fn($q) => $q->where('property_id', $propertyId))
                ->sum('amount');

            Log::info('PaymentService: Tenant caution amount calculation completed', [
                'tenant_id' => $tenantId,
                'property_id' => $propertyId,
                'caution_amount' => $amount
            ]);

            return $amount;
        } catch (InvalidArgumentException $e) {
            Log::error('PaymentService: Validation error in getTenantCautionAmount', [
                'tenant_id' => $tenantId,
                'property_id' => $propertyId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('PaymentService: Unexpected error in getTenantCautionAmount', [
                'tenant_id' => $tenantId,
                'property_id' => $propertyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Failed to get tenant caution amount: ' . $e->getMessage(), 0, $e);
        }
    }



    /**
     * Récupère les statistiques financières complètes pour une propriété
     */
    public function getPropertyFinancialStats(?int $propertyId, int $month, int $year): array
    {
        try {
            // Validate input parameters
            $this->validatePropertyId($propertyId);
            $this->validateMonthYear($month, $year);

            Log::info('PaymentService: Starting property financial stats calculation', [
                'property_id' => $propertyId,
                'month' => $month,
                'year' => $year
            ]);

            // Récupérer tous les appartements de la propriété
            $flats = $this->flatRepository->getByPropertyId($propertyId);

            if ($flats->isEmpty()) {
                Log::warning('PaymentService: No flats found for property', [
                    'property_id' => $propertyId
                ]);
                return [
                    'property_id' => $propertyId,
                    'month' => $month,
                    'year' => $year,
                    'total_revenue' => 0,
                    'total_commissions' => 0,
                    'total_flat_expenses' => 0,
                    'property_specific_expenses' => 0,
                    'total_expenses' => 0,
                    'amount_to_transfer' => 0,
                    'flat_stats' => []
                ];
            }

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

                Log::debug('PaymentService: Flat financial stats calculated', [
                    'flat_id' => $flat->id,
                    'revenue' => $flatRevenue,
                    'commission' => $flatCommission,
                    'expenses' => $flatExpenses
                ]);
            }

            // Récupérer les dépenses spécifiques à la propriété
            $propertyExpenses = $this->expenseRepository->calculateMonthlyExpensesForProperty($propertyId, $month, $year);

            // Montant à reverser = Revenus (tous paiements) - Commissions (loyers uniquement) - Dépenses
            $amountToTransfer = $totalRevenue - $totalCommissions - $propertyExpenses;

            $result = [
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

            Log::info('PaymentService: Property financial stats calculation completed', [
                'property_id' => $propertyId,
                'total_revenue' => $totalRevenue,
                'total_commissions' => $totalCommissions,
                'amount_to_transfer' => $amountToTransfer
            ]);

            return $result;
        } catch (InvalidArgumentException $e) {
            Log::error('PaymentService: Validation error in getPropertyFinancialStats', [
                'property_id' => $propertyId,
                'month' => $month,
                'year' => $year,
                'error' => $e->getMessage()
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('PaymentService: Unexpected error in getPropertyFinancialStats', [
                'property_id' => $propertyId,
                'month' => $month,
                'year' => $year,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Failed to get property financial stats: ' . $e->getMessage(), 0, $e);
        }
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
        try {
            // Validate input parameters
            $this->validateMonthYear($month, $year);

            // Flat ID can be null for property-level expenses
            if ($flatId !== null) {
                $this->validateFlatId($flatId);
            }

            Log::info('PaymentService: Starting monthly expenses calculation', [
                'flat_id' => $flatId,
                'month' => $month,
                'year' => $year
            ]);

            $expenses = $this->expenseRepository->calculateMonthlyExpenses($month, $year, $flatId);

            Log::info('PaymentService: Monthly expenses calculation completed', [
                'flat_id' => $flatId,
                'month' => $month,
                'year' => $year,
                'total_expenses' => $expenses
            ]);

            return $expenses;
        } catch (InvalidArgumentException $e) {
            Log::error('PaymentService: Validation error in calculateMonthlyExpenses', [
                'flat_id' => $flatId,
                'month' => $month,
                'year' => $year,
                'error' => $e->getMessage()
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('PaymentService: Unexpected error in calculateMonthlyExpenses', [
                'flat_id' => $flatId,
                'month' => $month,
                'year' => $year,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Failed to calculate monthly expenses: ' . $e->getMessage(), 0, $e);
        }
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
    public function calculateAmountToTransfer(?int $flatId, int $month, int $year): float
    {
        try {
            // Validate input parameters
            $this->validateFlatId($flatId);
            $this->validateMonthYear($month, $year);

            Log::info('PaymentService: Starting amount to transfer calculation for flat', [
                'flat_id' => $flatId,
                'month' => $month,
                'year' => $year
            ]);

            // Vérifier si l'appartement existe
            $flat = $this->flatRepository->findById($flatId);
            if (!$flat) {
                Log::error('PaymentService: Flat not found after validation', [
                    'flat_id' => $flatId
                ]);
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
            $amountToTransfer = $totalRevenue - $totalCommissions - $totalExpenses;

            Log::info('PaymentService: Amount to transfer calculation completed for flat', [
                'flat_id' => $flatId,
                'total_revenue' => $totalRevenue,
                'total_commissions' => $totalCommissions,
                'total_expenses' => $totalExpenses,
                'amount_to_transfer' => $amountToTransfer
            ]);

            return $amountToTransfer;
        } catch (InvalidArgumentException $e) {
            Log::error('PaymentService: Validation error in calculateAmountToTransfer', [
                'flat_id' => $flatId,
                'month' => $month,
                'year' => $year,
                'error' => $e->getMessage()
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('PaymentService: Unexpected error in calculateAmountToTransfer', [
                'flat_id' => $flatId,
                'month' => $month,
                'year' => $year,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Failed to calculate amount to transfer: ' . $e->getMessage(), 0, $e);
        }
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

    public function calculateLoyerTransferAmount(?int $propertyId, int $month, int $year): float
    {
        try {
            // Validate input parameters
            $this->validatePropertyId($propertyId);
            $this->validateMonthYear($month, $year);

            Log::info('PaymentService: Starting loyer transfer amount calculation', [
                'property_id' => $propertyId,
                'month' => $month,
                'year' => $year
            ]);

            $flats = $this->flatRepository->getByPropertyId($propertyId);

            if ($flats->isEmpty()) {
                Log::warning('PaymentService: No flats found for property', [
                    'property_id' => $propertyId
                ]);
                return 0;
            }

            $total = 0;

            foreach ($flats as $flat) {
                $payments = $this->paymentRepository->getMonthlyPaymentsByFlatAndType($month, $year, $flat->id, PaymentType::LOYER);
                $revenue = $payments->sum('amount');
                $commissions = $this->getTotalCommissionsForPayments($payments);
                $expenses = $this->expenseRepository->calculateMonthlyExpenses($month, $year, $flat->id);

                $flatTotal = $revenue - $commissions - $expenses;
                $total += $flatTotal;

                Log::debug('PaymentService: Flat calculation completed', [
                    'flat_id' => $flat->id,
                    'revenue' => $revenue,
                    'commissions' => $commissions,
                    'expenses' => $expenses,
                    'flat_total' => $flatTotal
                ]);
            }

            Log::info('PaymentService: Loyer transfer amount calculation completed', [
                'property_id' => $propertyId,
                'total_amount' => $total
            ]);

            return $total;
        } catch (InvalidArgumentException $e) {
            Log::error('PaymentService: Validation error in calculateLoyerTransferAmount', [
                'property_id' => $propertyId,
                'month' => $month,
                'year' => $year,
                'error' => $e->getMessage()
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('PaymentService: Unexpected error in calculateLoyerTransferAmount', [
                'property_id' => $propertyId,
                'month' => $month,
                'year' => $year,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Failed to calculate loyer transfer amount: ' . $e->getMessage(), 0, $e);
        }
    }

    public function calculateCautionTransferAmount(?int $propertyId): float
    {
        try {
            // Validate input parameters
            $this->validatePropertyId($propertyId);

            Log::info('PaymentService: Starting caution transfer amount calculation', [
                'property_id' => $propertyId
            ]);

            $flats = $this->flatRepository->getByPropertyId($propertyId);

            if ($flats->isEmpty()) {
                Log::warning('PaymentService: No flats found for property', [
                    'property_id' => $propertyId
                ]);
                return 0;
            }

            $total = 0;

            foreach ($flats as $flat) {
                $payments = $this->paymentRepository->getPaymentsByFlatAndType($flat->id, PaymentType::CAUTION);
                $flatTotal = $payments->sum('amount');
                $total += $flatTotal;

                Log::debug('PaymentService: Flat caution calculation completed', [
                    'flat_id' => $flat->id,
                    'caution_amount' => $flatTotal
                ]);
            }

            Log::info('PaymentService: Caution transfer amount calculation completed', [
                'property_id' => $propertyId,
                'total_amount' => $total
            ]);

            return $total;
        } catch (InvalidArgumentException $e) {
            Log::error('PaymentService: Validation error in calculateCautionTransferAmount', [
                'property_id' => $propertyId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('PaymentService: Unexpected error in calculateCautionTransferAmount', [
                'property_id' => $propertyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Failed to calculate caution transfer amount: ' . $e->getMessage(), 0, $e);
        }
    }
}
