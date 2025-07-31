<?php

namespace App\Services;

use App\Enums\PaymentType;
use App\Models\Payment;
use App\Models\Versement;
use App\Repositories\PaymentRepository;
// use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Support\Carbon;
use Spatie\Browsershot\Browsershot;

class DocumentGeneratorService
{
    protected $paymentRepository;
    public function __construct(
        PaymentRepository $paymentRepository,
    ) {
        $this->paymentRepository = $paymentRepository;
    }
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

        return $this->generatePdf('pdfs.quittance_simple', $data);
    }

    /**
     * Générer un reçu pour un versement partiel
     */
    public function generateRecu(Versement $versement): string
    {
        $payment = $versement->payment;

        if (!$payment) {
            throw new \InvalidArgumentException('Le versement n\'a pas de paiement associé');
        }

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
     * Vérifier si une facture peut être générée pour ce paiement
     */
    public function canGenerateFacture(Payment $payment): bool
    {
        // Vérifier le type
        if ($payment->type !== PaymentType::LOYER) {
            return false;
        }

        try {
            $flat = $payment->flat;
            $tenant = $payment->tenant;

            $loyer = $this->paymentRepository->getCurrentLoyerByFlat($flat);
            $arrieres = $this->paymentRepository->getArrears($tenant->id, $flat->id, $payment->current_month);
            $montantTotal = $this->paymentRepository->getTotalToPay($loyer, $arrieres);

            // Vérifier s'il y a des montants à payer
            if ($montantTotal <= 0) {
                return false;
            }

            // Vérifier si le tenant a des arriérés OU si c'est le mois actuel/futur
            $hasArrieres = $arrieres > 0;
            $isCurrentMonth = $this->isCurrentOrFutureMonth($payment->current_month);

            return $hasArrieres || $isCurrentMonth;
        } catch (\Exception $e) {
            return false;
        }
    }
    /**
     * Générer une facture PDF
     */
    public function generateFactureLoyer(Payment $payment): string
    {
        if ($payment->type !== PaymentType::LOYER) {
            throw new \InvalidArgumentException('Seules les factures de type LOYER peuvent être générées.');
        }

        // Utiliser notre méthode de validation
        if (!$this->canGenerateFacture($payment)) {
            throw new \RuntimeException("Impossible de générer la facture pour ce paiement.");
        }


        $data = $this->prepareBaseData($payment);
        $data['numero_document'] = $this->generateDocumentNumber('F', $payment->id);
        return $this->generatePdf('pdfs.facture_loyer', $data);
    }

    /**
     * Vérifier si le mois donné est le mois actuel ou futur
     */
    private function isCurrentOrFutureMonth(string $month): bool
    {
        $currentMonth = Carbon::now()->format('Y-m');
        $targetMonth = Carbon::createFromFormat('Y-m', $month);
        $currentMonthCarbon = Carbon::createFromFormat('Y-m', $currentMonth);

        return $targetMonth->greaterThanOrEqualTo($currentMonthCarbon);
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
        $mois = $payment->current_month;
        $type = $payment->type;
        $tenant = $payment->tenant;
        $flat = $payment->flat;

        $data = [
            'payment' => $payment,
            'tenant' => $tenant,
            'flat' => $flat,
            'date_generation' => now(),
            'montant_total' => $payment->amount,
            'montant_verse' => $payment->amount_paid,
            'montant_restant' => $payment->amount - $payment->amount_paid,

        ];

        if ($type === PaymentType::LOYER) {
            $mois = $payment->current_month;


            try {
                $month = Carbon::createFromFormat('Y-m', $mois);
            } catch (\Exception $e) {
                throw new \RuntimeException("Le champ current_month est invalide ou absent pour une facture LOYER : '$mois'");
            }


            $loyer = $this->paymentRepository->getCurrentLoyerByFlat($flat);
            $arrieres = $this->paymentRepository->getArrears($tenant->id, $flat->id, $mois);
            $montantTotal = $this->paymentRepository->getTotalToPay($loyer, $arrieres);
            $montantRestant = $this->paymentRepository->getRemainingAmount($montantTotal, $payment->amount_paid);

            $data = array_merge($data, [
                'mois' => $mois,
                'type' => $type,
                'arrieres' => $arrieres,
                'montant_total' => $montantTotal,
                'montant_restant' => $montantRestant,
                'montant_verse' => $payment->amount_paid,
                'periode_debut' => $month->copy()->startOfMonth()->format('d/m/Y'),
                'periode_fin' => $month->copy()->endOfMonth()->format('d/m/Y'),
            ]);
        }
        return $data;
    }


    // private function prepareBaseData(Payment $payment): array
    // {
    //     $mois = $payment->current_month;
    //     $month = Carbon::createFromFormat('Y-m', $mois);

    //     $tenant = $payment->tenant;

    //     // On recupère tous les impayés du mois non payé
    //     $impayes = $tenant->payment()
    //     ->where('status', 0)
    //     ->get();

    //     $remainingAmount = $payment->flat->loyer - $impayes->sum('amount');

    //     return [
    //         'payment' => $payment,
    //         'tenant' => $payment->tenant,
    //         'date_generation' => now(),
    //         'mois' => $mois,
    //         'montant_total' => $payment->flat->loyer + $impayes->sum('amount'),
    //         'montant_verse' => $payment->amount_paid,
    //         'montant_restant' => $remainingAmount, // Montant restant après versements
    //         'impayes' => $impayes,            // 'montant_restant' => $payment->amount - $payment->amount_paid,
    //         'flat' => $payment->flat,
    //         // Ajout de la période calculée
    //         'periode_debut' => $month->copy()->startOfMonth()->format('d/m/Y'),
    //         'periode_fin' => $month->copy()->endOfMonth()->format('d/m/Y'),
    //     ];
    // }

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

    // générer un numéro de document unique pour la reçu


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
        $html = view($view, $data)->render();

        return Browsershot::html($html)
            ->format('A5')
            ->margins(10, 10, 10, 10)
            ->noSandbox() // Important si tu es en local sans root
            ->disableGpu()
            ->waitUntilNetworkIdle() // Optionnel : attend le chargement total
            ->pdf(); // Retourne le binaire du PDF
    }
}
