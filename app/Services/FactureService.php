<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Flat;
use App\Models\Tenant;
use App\Enums\PaymentType;
use App\Enums\RemittanceType;
use App\Models\Remittance;
use App\Repositories\FlatRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class FactureService
{
    protected FlatRepository $flatRepository;

    public function __construct(FlatRepository $flatRepository)
    {
        $this->flatRepository = $flatRepository;
    }

    /**
     * Générer un numéro de facture unique selon le type
     */
    public function generateUniqueNumero(PaymentType $type): string
    {
        $prefix = $type->getPrefix();
        $year = Carbon::now()->format('Y');
        $month = Carbon::now()->format('m');

        // Compteur séquentiel par type, année et mois pour éviter les collisions
        $count = Payment::where('type', $type->value)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count() + 1;

        $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}{$month}-{$sequence}";
    }

    /**
     * Générer un numéro de remittance unique selon le type
     */

    public function generateUniqueNumber(RemittanceType $type): string
    {
        $prefix = $type->getPrefix();
        $year = Carbon::now()->format('Y');
        $month = Carbon::now()->format('m');

        // Compteur séquentiel par type, année et mois pour éviter les collisions
        $count = Remittance::where('remittance_type', $type->value)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count() + 1;

        $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}{$month}-{$sequence}";
    }
    /**
     * Calculer le montant selon le type de facture et l'appartement
     */
    public function calculateAmountByType(PaymentType $type, ?Flat $flat): float
    {
        if (!$flat) {
            throw new \InvalidArgumentException('Appartement introuvable pour ce locataire.');
        }
        return match ($type) {
            PaymentType::LOYER => (float) $flat->loyer,
            PaymentType::CAUTION => (float) $flat->caution,
            PaymentType::COMMISSION => $this->flatRepository->calculateCommission($flat, $flat->loyer),
        };
    }

    /**
     * Vérifie si une caution existe déjà pour ce contrat spécifique
     */
    public function cautionAlreadyExists(int $tenantId, int $flatId, ?int $contractId = null): bool
    {
        $query = Payment::where('tenant_id', $tenantId)
            ->where('flat_id', $flatId)
            ->where('type', PaymentType::CAUTION->value);

        // Si un contract_id est fourni, vérifier spécifiquement pour ce contrat
        if ($contractId) {
            $query->where('contract_id', $contractId);
        }

        return $query->exists();
    }

    /**
     * Vérifie si une facture du même type existe déjà pour ce mois et ce contrat
     */
    public function paymentExistsForMonthAndContract(PaymentType $type, int $contractId, ?string $month): bool
    {
        // Pour la caution, pas de vérification de mois (elle est unique)
        if ($type === PaymentType::CAUTION) {
            return false;
        }

        if (!$month) {
            return false;
        }

        return Payment::where('contract_id', $contractId)
            ->where('type', $type->value)
            ->where('current_month', $month)
            ->exists();
    }

    /**
     * Vérifie si un loyer existe déjà pour ce mois
     */
    public function loyerAlreadyExistsForMonth(int $tenantId, int $flatId, ?string $month): bool
    {
        if (!$month) return false;

        return Payment::where('tenant_id', $tenantId)
            ->where('flat_id', $flatId)
            ->where('type', PaymentType::LOYER->value)
            ->where('current_month', $month)
            ->exists();
    }

    public function canCreatePayment(PaymentType $type, int $tenantId, int $flatId, ?string $month = null, ?int $contractId = null): array
    {
        $canCreate = true;
        $message = '';

        switch ($type) {
            case PaymentType::CAUTION:
                // Vérifier spécifiquement pour ce contrat si fourni
                if ($this->cautionAlreadyExists($tenantId, $flatId, $contractId)) {
                    $canCreate = false;
                    if ($contractId) {
                        $message = 'Une caution existe déjà pour ce contrat spécifique.';
                    } else {
                        $message = 'Une caution existe déjà pour ce locataire dans cet appartement.';
                    }
                }
                break;

            case PaymentType::LOYER:
            case PaymentType::COMMISSION:
                // Vérification principale : facture du même type pour le même mois et contrat
                if ($contractId && $month && $this->paymentExistsForMonthAndContract($type, $contractId, $month)) {
                    $canCreate = false;
                    $typeLabel = $type->getLabel();
                    $message = "Une facture de type '{$typeLabel}' existe déjà pour ce contrat dans le mois de {$month}.";
                }

                // Vérification supplémentaire pour le loyer (legacy)
                if ($canCreate && $type === PaymentType::LOYER && $month && $this->loyerAlreadyExistsForMonth($tenantId, $flatId, $month)) {
                    $canCreate = false;
                    $message = "Un loyer existe déjà pour le mois de {$month}.";
                }
                break;
        }

        return [
            'can_create' => $canCreate,
            'message' => $message
        ];
    }
    /**
     * Prépare les données pour la création d'un paiement
     */
    public function preparePaymentData(array $formData): array
    {
        $type = PaymentType::from($formData['type']);
        $tenant = Tenant::with('flatThroughContract')->find($formData['tenant_id']);

        if (!$tenant || !$tenant->flatThroughContract) {
            throw new \InvalidArgumentException('Locataire ou appartement invalide');
        }

        $flat = $tenant->flatThroughContract;
        $month = $formData['current_month'] ?? null;
        $contractId = $formData['contract_id'] ?? null;

        // Validation avec le contract_id pour une vérification précise
        $validation = $this->canCreatePayment($type, $tenant->id, $flat->id, $month, $contractId);
        if (!$validation['can_create']) {
            throw new \Exception($validation['message']);
        }

        $amount = $this->calculateAmountByType($type, $flat);

        return [
            'numero' => $this->generateUniqueNumero($type),
            'type' => $type->value,
            'tenant_id' => $tenant->id,
            'flat_id' => $flat->id,
            'contract_id' => $contractId,
            'amount' => $amount,
            'current_month' => $month,
            'date_payment' => $formData['date_payment'],
            'status' => false,
            'amount_paid' => 0,
            'amount_remaining' => $amount,
        ];
    }

    /**
     * Obtient le libellé complet pour un type de paiement
     */
    public function getPaymentLabel(PaymentType $type, ?string $month = null): string
    {
        $label = $type->getLabel();

        if ($type === PaymentType::LOYER && $month) {
            $label .= " - {$month}";
        }

        return $label;
    }

    /**
     * Vérifie si un contrat est valide pour un paiement
     */
    public function validateContract(int $contractId, int $tenantId): bool
    {
        return DB::table('contracts')
            ->where('id', $contractId)
            ->where('tenant_id', $tenantId)
            ->exists();
    }
    /**
     * Obtient les factures existantes pour un contrat et un mois donné
     */
    public function getExistingPaymentsForMonth(int $contractId, string $month): array
    {
        return Payment::where('contract_id', $contractId)
            ->where('current_month', $month)
            ->with(['tenant', 'flat'])
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'numero' => $payment->numero,
                    'type' => $payment->type->getLabel(),
                    'amount' => $payment->amount,
                ];
            })
            ->toArray();
    }

    /**
     * Vérifie et retourne les détails de validation pour un contrat/mois
     */
    public function validatePaymentCreation(PaymentType $type, int $contractId, ?string $month): array
    {
        if (!$month || $type === PaymentType::CAUTION) {
            return ['can_create' => true, 'message' => '', 'existing_payments' => []];
        }

        $existingPayments = $this->getExistingPaymentsForMonth($contractId, $month);
        $sameTypeExists = collect($existingPayments)->contains('type', $type->getLabel());

        if ($sameTypeExists) {
            $existingPayment = collect($existingPayments)->first(fn($p) => $p['type'] === $type->getLabel());
            return [
                'can_create' => false,
                'message' => "Une facture de type '{$type->getLabel()}' (N° {$existingPayment['numero']}) existe déjà pour ce contrat dans le mois de {$month}.",
                'existing_payments' => $existingPayments
            ];
        }

        // Information sur les autres types de factures existantes
        $otherTypes = collect($existingPayments)->pluck('type')->unique()->implode(', ');
        $infoMessage = '';
        if ($otherTypes) {
            $infoMessage = "Autres factures existantes pour ce mois : {$otherTypes}";
        }

        return [
            'can_create' => true,
            'message' => $infoMessage,
            'existing_payments' => $existingPayments
        ];
    }
}
