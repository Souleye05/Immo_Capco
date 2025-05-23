<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Versement;
use App\Models\Unsold;
use Illuminate\Support\Facades\DB;

class VersementService
{
    /**
     * Valider la création d'un versement
     */
    public function validateVersementCreation(Payment $payment, float $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Le montant du versement doit être supérieur à 0');
        }

        if ($payment->status == 1) {
            throw new \InvalidArgumentException('Cette facture a déjà été réglée.');
        }

        $totalVersements = $this->getTotalVersements($payment->id);
        $montantMaxAutorise = $payment->amount - $totalVersements;

        if ($amount > $montantMaxAutorise) {
            throw new \InvalidArgumentException(
                "Le total des versements dépasserait le montant dû. Montant maximum autorisé: " . 
                number_format($montantMaxAutorise, 0, ',', ' ') . ' FCFA'
            );
        }
    }

    /**
     * Traiter les actions après création d'un versement
     */
    public function processVersementCreated(int $paymentId): array
    {
        $payment = Payment::findOrFail($paymentId);
        $totalVersements = $this->getTotalVersements($paymentId);

        if ($totalVersements >= $payment->amount) {
            return $this->handleFullPayment($payment);
        }

        return [
            'title' => 'Versement enregistré',
            'message' => 'Le versement a été enregistré. Montant restant: ' . 
                        number_format($payment->amount - $totalVersements, 0, ',', ' ') . ' FCFA'
        ];
    }

    /**
     * Gérer un paiement complet
     */
    private function handleFullPayment(Payment $payment): array
    {
        // Traiter les impayés
        $unsoldsResolved = $this->resolveUnsolds($payment->tenant_id);
        
        // Mettre à jour le statut du paiement
        $this->updatePaymentStatus($payment->id, 1);

        $message = "Le locataire a payé son loyer complet.";
        
        if ($unsoldsResolved['count'] > 0) {
            $message .= " Impayés réglés: " . 
                       number_format($unsoldsResolved['total'], 0, ',', ' ') . " FCFA";
        }

        return [
            'title' => 'Paiement complet',
            'message' => $message
        ];
    }

    /**
     * Résoudre les impayés d'un locataire
     */
    private function resolveUnsolds(int $tenantId): array
    {
        $unsolds = Unsold::where('tenant_id', $tenantId)->get();
        
        if ($unsolds->isEmpty()) {
            return ['count' => 0, 'total' => 0];
        }

        $totalAmount = $unsolds->sum('amount');
        
        foreach ($unsolds as $unsold) {
            $unsold->paid_at = now();
            $unsold->save();
            $unsold->delete(); // Soft delete
        }

        return [
            'count' => $unsolds->count(),
            'total' => $totalAmount
        ];
    }

    /**
     * Gérer la suppression d'un versement
     */
    public function handleVersementDeleted(int $paymentId): void
    {
        $payment = Payment::findOrFail($paymentId);
        $totalVersements = $this->getTotalVersements($paymentId);

        $newStatus = $totalVersements < $payment->amount ? 0 : 1;
        $this->updatePaymentStatus($paymentId, $newStatus);
    }

    /**
     * Obtenir le total des versements pour un paiement
     */
    private function getTotalVersements(int $paymentId): float
    {
        return DB::table('versements')
            ->where('payment_id', $paymentId)
            ->sum('amount');
    }

    /**
     * Mettre à jour le statut d'un paiement
     */
    private function updatePaymentStatus(int $paymentId, int $status): void
    {
        DB::table('payments')
            ->where('id', $paymentId)
            ->update(['status' => $status]);
    }

    /**
     * Vérifier si un paiement est éligible pour une quittance détaillée
     */
    public function isEligibleForDetailedQuittance(Payment $payment): bool
    {
        return $payment->is_fully_paid && $payment->versement()->count() > 1;
    }

    /**
     * Obtenir les statistiques d'un paiement
     */
    public function getPaymentStatistics(Payment $payment): array
    {
        $versements = $payment->versement;
        $totalVerse = $versements->sum('amount');
        
        return [
            'montant_total' => $payment->amount,
            'montant_verse' => $totalVerse,
            'montant_restant' => max(0, $payment->amount - $totalVerse),
            'nombre_versements' => $versements->count(),
            'is_fully_paid' => $totalVerse >= $payment->amount,
            'versements' => $versements->sortBy('versement_date')
        ];
    }
}