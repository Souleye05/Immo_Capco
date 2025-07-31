<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Versement;
use App\Services\DocumentGeneratorService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentGeneratorService $documentGenerator
    ) {}

    /**
     * Télécharger un reçu pour un paiement
     */
    public function downloadRecu(Payment $payment): Response
    {
        try {
            // Pour un paiement, nous générons une facture de loyer
            if ($this->documentGenerator->canGenerateFacture($payment)) {
                $pdf = $this->documentGenerator->generateFactureLoyer($payment);
                $filename = $this->generateFilename('facture', $payment->id, now());
            } else {
                // Fallback : générer un document simple
                return response(['error' => 'Impossible de générer un document pour ce paiement'], 400);
            }

            return $this->createPdfResponse($pdf, $filename);
        } catch (\Exception $e) {
            Log::error('Erreur génération reçu', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);

            return response(['error' => 'Erreur lors de la génération du reçu'], 500);
        }
    }

    /**
     * Télécharger une quittance pour un paiement complet
     */
    public function downloadQuittance(Payment $payment): Response
    {
        try {
            $pdf = $this->documentGenerator->generateAppropriateDocument($payment);
            $filename = $this->generateFilename('quittance', $payment->id, now());

            return $this->createPdfResponse($pdf, $filename);
        } catch (\Exception $e) {
            Log::error('Erreur génération quittance', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);

            return response(['error' => 'Erreur lors de la génération de la quittance'], 500);
        }
    }

    /**
     * Télécharger une quittance détaillée depuis un versement
     */
    public function downloadQuittanceFromVersement(Versement $versement): Response
    {
        try {
            $payment = $versement->payment;

            if (!$payment->is_fully_paid) {
                return response(['error' => 'Le paiement n\'est pas encore complet'], 400);
            }

            $pdf = $this->documentGenerator->generateQuittanceDetaillee($payment);
            $filename = $this->generateFilename('quittance_detaillee', $payment->id, now());

            return $this->createPdfResponse($pdf, $filename);
        } catch (\Exception $e) {
            Log::error('Erreur génération quittance détaillée', [
                'versement_id' => $versement->id,
                'payment_id' => $versement->payment_id,
                'error' => $e->getMessage()
            ]);

            return response(['error' => 'Erreur lors de la génération de la quittance'], 500);
        }
    }

    /**
     * Télécharger une facture pour un paiement
     */
    public function downloadFacture(Payment $payment): Response
    {
        try {
            $pdf = $this->documentGenerator->generateFactureLoyer($payment);
            $filename = $this->generateFilename('facture', $payment->id, now());

            return $this->createPdfResponse($pdf, $filename);
        } catch (\Exception $e) {
            Log::error('Erreur génération facture', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);

            return response(['error' => 'Erreur lors de la génération de la facture'], 500);
        }
    }

    /**
     * Créer une réponse HTTP avec le PDF
     */
    private function createPdfResponse(string $pdf, string $filename): Response
    {
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    }

    /**
     * Générer un nom de fichier approprié
     */
    private function generateFilename(string $type, int $id, $date): string
    {
        $dateFormatted = is_string($date) ? $date : $date->format('Y-m-d');
        return sprintf('%s_%d_%s.pdf', $type, $id, $dateFormatted);
    }
}
