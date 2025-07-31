<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Contract;
use Illuminate\Console\Command;

class FixPaymentFlatIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:fix-flat-ids {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige les flat_id des paiements selon leur contract_id';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('Mode dry-run activé. Aucune modification ne sera effectuée.');
        }

        // Trouver tous les paiements qui ont un contract_id mais dont le flat_id ne correspond pas
        $paymentsToFix = Payment::whereNotNull('contract_id')
            ->with(['contract.flat', 'flat'])
            ->get()
            ->filter(function ($payment) {
                return $payment->contract &&
                    $payment->contract->flat_id !== $payment->flat_id;
            });

        if ($paymentsToFix->isEmpty()) {
            $this->info('Aucun paiement à corriger trouvé.');
            return 0;
        }

        $this->info("Trouvé {$paymentsToFix->count()} paiement(s) à corriger.");

        $updated = 0;
        $errors = 0;

        foreach ($paymentsToFix as $payment) {
            try {
                $oldFlatId = $payment->flat_id;
                $newFlatId = $payment->contract->flat_id;

                $oldFlatRef = $payment->flat ? $payment->flat->reference : 'N/A';
                $newFlatRef = $payment->contract->flat ? $payment->contract->flat->reference : 'N/A';

                if ($isDryRun) {
                    $this->line("[DRY-RUN] Paiement {$payment->numero} (Contrat: {$payment->contract->contract_number})");
                    $this->line("  Ancien flat_id: {$oldFlatId} ({$oldFlatRef})");
                    $this->line("  Nouveau flat_id: {$newFlatId} ({$newFlatRef})");
                } else {
                    $payment->update(['flat_id' => $newFlatId]);
                    $this->line("✅ Paiement {$payment->numero} (Contrat: {$payment->contract->contract_number})");
                    $this->line("  Flat_id mis à jour: {$oldFlatId} ({$oldFlatRef}) → {$newFlatId} ({$newFlatRef})");
                }
                $updated++;
            } catch (\Exception $e) {
                $this->error("❌ Erreur pour le paiement {$payment->numero}: {$e->getMessage()}");
                $errors++;
            }
        }

        if ($isDryRun) {
            $this->info("Résumé (dry-run): {$updated} paiement(s) seraient mis à jour, {$errors} erreur(s).");
            $this->info("Exécutez sans --dry-run pour appliquer les modifications.");
        } else {
            $this->info("Résumé: {$updated} paiement(s) mis à jour, {$errors} erreur(s).");
        }

        // Afficher un résumé par locataire
        if (!$isDryRun && $updated > 0) {
            $this->info("\n📋 Résumé par locataire:");

            $paymentsByTenant = Payment::whereIn('id', $paymentsToFix->pluck('id'))
                ->with(['tenant', 'flat.property', 'contract'])
                ->get()
                ->groupBy('tenant.name');

            foreach ($paymentsByTenant as $tenantName => $payments) {
                $this->line("  {$tenantName}:");
                foreach ($payments as $payment) {
                    $property = $payment->flat->property->name ?? 'N/A';
                    $flatRef = $payment->flat->reference ?? 'N/A';
                    $this->line("    - {$payment->numero} → {$property} - {$flatRef}");
                }
            }
        }

        return 0;
    }
}
