<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Versement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;

class DocumentGeneratorService
{
    const DOCUMENT_TYPE_QUITTANCE_SIMPLE = 'quittance_simple';
    const DOCUMENT_TYPE_QUITTANCE_DETAILLEE = 'quittance_detaillee';
    const DOCUMENT_TYPE_RECU = 'recu';

    /**
     * Générer une quittance simple pour un paiement complet sans versements partiels
     */
    public function generateQuittanceSimple(Payment $payment): string
    {
        $this->validatePaymentForQuittance($payment);
        
        $data = $this->prepareBaseData($payment);
        $data['numero_document'] = $this->generateDocumentNumber('Q', $payment->id);
        $data['type_document'] = self::DOCUMENT_TYPE_QUITTANCE_SIMPLE;
        
        return $this->generatePdf('pdfs.quittance_simple', $data);
    }

    /**
     * Générer une quittance détaillée pour un paiement complet avec versements partiels
     */
    public function generateQuittanceDetaillee(Payment $payment): string
    {
        $this->validatePaymentForQuittance($payment);
        
        $versements = $payment->versement()->orderBy('versement_date')->get();
        
        if ($versements->isEmpty()) {
            throw new \InvalidArgumentException('Aucun versement trouvé pour ce paiement');
        }

        $data = $this->prepareBaseData($payment);
        $data['versements'] = $versements;
        $data['numero_document'] = $this->generateDocumentNumber('QD', $payment->id);
        $data['type_document'] = self::DOCUMENT_TYPE_QUITTANCE_DETAILLEE;
        $data['total_versements'] = $versements->sum('amount');
        $data['nombre_versements'] = $versements->count();
        
        return $this->generatePdf('pdfs.quittance_detaillee', $data);
    }

    /**
     * Générer un reçu pour un versement partiel
     */
    public function generateRecu(Versement $versement): string
    {
        $payment = $versement->payment;
        $this->ensureVersementDate($versement);
        
        $montants = $this->calculateMontants($versement);
        
        $data = $this->prepareBaseData($payment);
        $data['versement'] = $versement;
        $data['numero_document'] = $this->generateDocumentNumber('R', $versement->id);
        $data['type_document'] = self::DOCUMENT_TYPE_RECU;
        $data = array_merge($data, $montants);
        
        return $this->generatePdf('pdfs.recu', $data);
    }

    /**
     * Déterminer le type de document à générer selon le contexte
     */
    public function generateAppropriateDocument(Payment $payment): string
    {
        if (!$payment->is_fully_paid) {
            throw new \InvalidArgumentException('Le paiement n\'est pas encore complet');
        }

        $versements = $payment->versement;
        
        // Si un seul versement = quittance simple
        if ($versements->count() <= 1) {
            return $this->generateQuittanceSimple($payment);
        }
        
        // Si plusieurs versements = quittance détaillée
        return $this->generateQuittanceDetaillee($payment);
    }

    /**
     * Préparer les données de base communes à tous les documents
     */
    private function prepareBaseData(Payment $payment): array
    {
        return [
            'payment' => $payment,
            'tenant' => $payment->tenant,
            'date_generation' => now(),
            'montant_total' => $payment->amount,
        ];
    }

    /**
     * Générer un numéro de document unique
     */
    private function generateDocumentNumber(string $prefix, int $id): string
    {
        return sprintf('%s-%s-%d', $prefix, date('Ymd'), $id);
    }

    /**
     * Calculer les montants pour un reçu de versement partiel
     */
    private function calculateMontants(Versement $versement): array
    {
        $payment = $versement->payment;
        
        // Montant total des versements jusqu'à ce versement (inclus)
        $versementsCumules = $payment->versement()
            ->where('versement_date', '<=', $versement->versement_date)
            ->where('id', '<=', $versement->id)
            ->sum('amount');
            
        $montantRestant = max(0, $payment->amount - $versementsCumules);
        
        return [
            'montant_verse' => $versement->amount,
            'montant_cumule' => $versementsCumules,
            'montant_restant' => $montantRestant,
        ];
    }

    /**
     * Valider qu'un paiement peut avoir une quittance
     */
    private function validatePaymentForQuittance(Payment $payment): void
    {
        if (!$payment->is_fully_paid) {
            throw new \InvalidArgumentException('Une quittance ne peut être générée que pour un paiement complet');
        }
    }

    /**
     * S'assurer qu'un versement a une date
     */
    private function ensureVersementDate(Versement $versement): void
    {
        if (!$versement->versement_date) {
            $versement->versement_date = now();
            $versement->save();
        }
    }

    /**
     * Générer le PDF avec les données fournies
     */
    private function generatePdf(string $view, array $data): string
    {
        $pdf = PDF::loadView($view, $data);
        return $pdf->output();
    }
}