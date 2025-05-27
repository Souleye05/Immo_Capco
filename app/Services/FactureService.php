<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Flat;
use App\Models\Tenant;
use App\Enums\PaymentType;
use App\Repositories\FlatRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FactureService
{
  protected $flatRepository;
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
        $randomNumber = random_int(100000, 999999);
        
        return "{$prefix}-{$randomNumber}";
    }

    /**
     * Calculer le montant selon le type de facture et l'appartement
     */
    public function calculateAmountByType(PaymentType $type, Flat $flat): float
    {
        return match($type) {
            PaymentType::LOYER => $flat->loyer,
            PaymentType::CAUTION => $flat->caution,
            PaymentType::COMMISSION => $this->flatRepository->calculateCommission($flat, $flat->loyer),
        };
    }

    /**
     * Vérifie si une caution existe déjà pour ce locataire/appartement
     */
    public function cautionAlreadyExists(int $tenantId, int $flatId): bool
    {
        return Payment::where('tenant_id', $tenantId)
            ->where('flat_id', $flatId)
            ->where('type', PaymentType::CAUTION->value)
            ->exists();
    }

    /**
     * Vérifie si un paiement peut être créé selon les règles métier
     */
    public function canCreatePayment(PaymentType $type, int $tenantId, int $flatId): array
    {
        $canCreate = true;
        $message = '';

        switch ($type) {
            case PaymentType::CAUTION:
                if ($this->cautionAlreadyExists($tenantId, $flatId)) {
                    $canCreate = false;
                    $message = 'Une caution existe déjà pour ce locataire dans cet appartement.';
                }
                break;

            case PaymentType::LOYER:
            case PaymentType::COMMISSION:
                // Pas de restriction particulière pour le moment
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
        $tenant = Tenant::with('flat')->find($formData['tenant_id']);
        $flat = $tenant->flat;

        // Validation
        $validation = $this->canCreatePayment($type, $tenant->id, $flat->id);
        if (!$validation['can_create']) {
            throw new \Exception($validation['message']);
        }

        return [
            'numero' => $this->generateUniqueNumero($type),
            'payment_type' => $type->value,
            'tenant_id' => $tenant->id,
            'flat_id' => $flat->id,
            'amount' => $this->calculateAmountByType($type, $flat),
            'current_month' => $formData['current_month'] ?? null,
            'date_payment' => $formData['date_payment'],
            'status' => false, // Non payé par défaut
            'amount_paid' => 0,
            'amount_remaining' => $this->calculateAmountByType($type, $flat),
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
}