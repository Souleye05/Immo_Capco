<?php

namespace App\Services;

use App\Enums\PaymentType;
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

        // Obtenir le message selon le type de paiement
        $paymentTypeMessage = $this->getPaymentTypeMessage($payment->type);
        
        $message = $paymentTypeMessage['complete_message'];
        
        if ($unsoldsResolved['count'] > 0) {
            $message .= " Impayés réglés: " . 
                       number_format($unsoldsResolved['total'], 0, ',', ' ') . " FCFA";
        }

        return [
            'title' => $paymentTypeMessage['title'],
            'message' => $message
        ];
    }

    /**
     * Obtenir les messages selon le type de paiement
     */
    private function getPaymentTypeMessage(PaymentType $paymentType): array
    {
        $messages = [
            PaymentType::LOYER->value => [
                'title' => 'Loyer payé intégralement',
                'complete_message' => 'Le locataire a payé son loyer complet.',
                'partial_message' => 'Versement sur le loyer enregistré.'
            ],
            PaymentType::CAUTION->value => [
                'title' => 'Caution payée intégralement',
                'complete_message' => 'Le locataire a payé sa caution complète.',
                'partial_message' => 'Versement sur la caution enregistré.'
            ],
            PaymentType::COMMISSION->value => [
                'title' => 'Commission payée intégralement',
                'complete_message' => 'La commission a été payée intégralement.',
                'partial_message' => 'Versement sur la commission enregistré.'
            ]
        ];

        return $messages[$paymentType->value] ?? [
            'title' => 'Paiement complet',
            'complete_message' => 'Le paiement a été effectué intégralement.',
            'partial_message' => 'Versement enregistré.'
        ];
    }

    /**
     * Traiter les actions après création d'un versement (version améliorée)
     */
    public function processVersementCreatedImproved(int $paymentId): array
    {
        $payment = Payment::findOrFail($paymentId);
        $totalVersements = $this->getTotalVersements($paymentId);

        if ($totalVersements >= $payment->amount) {
            return $this->handleFullPayment($payment);
        }

        // Message pour versement partiel selon le type
        $paymentTypeMessage = $this->getPaymentTypeMessage($payment->type);
        
        return [
            'title' => 'Versement enregistré',
            'message' => $paymentTypeMessage['partial_message'] . ' Montant restant: ' . 
                        number_format($payment->amount - $totalVersements, 0, ',', ' ') . ' FCFA'
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