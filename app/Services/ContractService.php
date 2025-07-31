<?php

namespace App\Services;

use App\Models\Contract;
use App\Enums\ContractStatus;
use App\Models\Flat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ContractService
{
    /**
     * Génère un numéro de contrat unique pour l'agence courante
     */
    public function generateContractNumber(?int $agencyId = null): string
    {
        $year = date('Y');

        // Obtenir l'ID de l'agence courante
        if (!$agencyId) {
            $agencyId = \Filament\Facades\Filament::getTenant()?->getKey();
        }

        if (!$agencyId) {
            throw new \Exception('Agency ID is required for contract number generation');
        }

        // Utiliser une transaction pour éviter les conditions de course
        return DB::transaction(function () use ($year, $agencyId) {
            $maxAttempts = 100; // Limite pour éviter les boucles infinies
            $attempts = 0;

            do {
                $attempts++;

                // Récupérer le dernier numéro pour cette année ET cette agence avec un verrou
                $lastContract = Contract::where('contract_number', 'LIKE', "CTR-{$year}-%")
                    ->where('contract_number', 'REGEXP', '^CTR-[0-9]{4}-[0-9]{4}$')
                    ->where('agency_id', $agencyId)
                    ->orderByRaw('CAST(SUBSTRING(contract_number, 10) AS UNSIGNED) DESC')
                    ->lockForUpdate()
                    ->first();

                $lastNumber = 0;
                if ($lastContract && preg_match('/^CTR-\d{4}-(\d{4})$/', $lastContract->contract_number, $matches)) {
                    $lastNumber = (int) $matches[1];
                }

                // Générer le prochain numéro
                $nextNumber = $lastNumber + 1;
                $contractNumber = "CTR-{$year}-" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

                // Vérifier l'unicité pour cette agence seulement
                $exists = Contract::where('contract_number', $contractNumber)
                    ->where('agency_id', $agencyId)
                    ->exists();

                if (!$exists) {
                    return $contractNumber;
                }

                // Si le numéro existe, attendre un peu avant de réessayer
                usleep(10000); // 10ms

            } while ($attempts < $maxAttempts);

            // Si on arrive ici, générer un numéro avec timestamp pour garantir l'unicité
            $timestamp = now()->format('His');
            return "CTR-{$year}-{$timestamp}";
        });
    }

    public function sendExpirationNotification(Contract $contract, string $type)
    {
        $daysUntilExpiration = Carbon::now()->diffInDays($contract->end_date, false);

        // Envoyer à l'admin
        // $contract->tenant->notify(new ContractExpirationNotification($contract, $type, $daysUntilExpiration));

        // Log dans la base de données
        $contract->notifications()->create([
            'type' => 'expiration_warning',
            'sent_at' => now(),
            'data' => [
                'warning_type' => $type,
                'days_until_expiration' => $daysUntilExpiration
            ]
        ]);
    }

    public function renewContract(Contract $contract, int $durationMonths, ?float $newRent = null): Contract
    {
        // Générer le numéro de contrat une seule fois
        $contractNumber = $this->generateContractNumber();

        // Créer un nouveau contrat basé sur l'ancien
        $newContract = Contract::create([
            'tenant_id' => $contract->tenant_id,
            'flat_id' => $contract->flat_id,
            'property_id' => $contract->property_id,
            'monthly_rent' => $newRent ?? $contract->monthly_rent,
            'cautions' => $contract->cautions,
            'start_date' => $contract->end_date->copy()->addDay(),
            'end_date' => $contract->end_date->copy()->addMonths($durationMonths),
            'notice_period_days' => $contract->notice_period_days,
            'alert_days_before' => $contract->alert_days_before,
            'auto_renewal' => $contract->auto_renewal,
            'renewal_duration_months' => $contract->renewal_duration_months,
            'status' => ContractStatus::ACTIVE,
            'notes' => "Renouvellement du contrat #{$contract->contract_number}",
            'contract_number' => $contractNumber,
        ]);

        // Archiver l'ancien contrat
        $contract->update([
            'status' => ContractStatus::RENEWED,
            'notes' => ($contract->notes ?? '') . "\nRenouvelé par le contrat #{$newContract->contract_number}"
        ]);

        // Envoyer la notification
        // $this->sendRenewalNotification($newContract, $contract);

        return $newContract;
    }

    public function autoRenewContract(Contract $contract): Contract
    {
        return $this->renewContract(
            $contract,
            $contract->renewal_duration_months ?? 12
        );
    }

    /**
     * Résilier un contrat
     */
    public function terminateContract(Contract $contract, ?string $reason = null): bool
    {
        $contract->update([
            'status' => ContractStatus::TERMINATED->value,
            'notes' => $contract->notes . "\nRésiliation: " . ($reason ?? 'Non spécifiée') . " - " . now()->format('d/m/Y H:i')
        ]);

        return true;
    }

    /**
     * Vérifie si un contrat doit envoyer une alerte
     */
    public function shouldSendAlert(Contract $contract): bool
    {
        return $contract->status === ContractStatus::ACTIVE->value &&
            $contract->end_date <= now()->addDays($contract->alert_days_before);
    }

    /**
     * Calcule les jours restants avant expiration
     */
    public function getDaysUntilExpiration(Contract $contract): int
    {
        $days = $contract->end_date->diffInDays(now(), false);
        return max($days, 0); // Retourne 0 si la date est passée
    }

    /**
     * Vérifie si un contrat est expiré
     */
    public function isExpired(Contract $contract): bool
    {
        return $contract->end_date < now() && $contract->status === ContractStatus::ACTIVE->value;
    }

    /**
     * Vérifie si un contrat expire bientôt
     */
    public function isExpiringSoon(Contract $contract): bool
    {
        return $contract->end_date <= now()->addDays($contract->alert_days_before);
    }

    /**
     * Récupère les informations d'un appartement pour pré-remplir le formulaire
     */
    public function getFlatInfo(int $flatId): array
    {
        $flat = Flat::with('property')->find($flatId);

        if (!$flat) {
            return [];
        }

        return [
            'monthly_rent' => $flat->loyer ?? null,
            'cautions' => $flat->caution ?? null,
            'designation' => $flat->designation ?? null,
            'address' => $flat->property->address ?? null,
        ];
    }

    /**
     * Calcule la durée totale d'un contrat en mois
     */
    public function getTotalDuration(Contract $contract): int
    {
        return $contract->start_date->diffInMonths($contract->end_date);
    }

    /**
     * Récupère tous les contrats expirant bientôt
     */
    public function getExpiringSoonContracts(int $days = 90): \Illuminate\Database\Eloquent\Collection
    {
        return Contract::where('status', ContractStatus::ACTIVE->value)
            ->where('end_date', '<=', now()->addDays($days))
            ->get();
    }

    /**
     * Récupère tous les contrats expirés
     */
    public function getExpiredContracts(): \Illuminate\Database\Eloquent\Collection
    {
        return Contract::where('status', ContractStatus::ACTIVE->value)
            ->where('end_date', '<', now())
            ->get();
    }
}
