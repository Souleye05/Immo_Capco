<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Contract;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateMonthlyPayments extends Command
{
    protected $signature = 'payments:generate';
    protected $description = 'Génère les factures mensuelles pour les contrats actifs';

    public function handle()
    {
        $currentMonth = Carbon::now()->format('Y-m');
        $targetDate = Carbon::now()->startOfMonth();
        $count = 0;

        $this->info("Génération des factures pour le mois de {$currentMonth}");

        // Récupérer les contrats actifs pour ce mois
        $contracts = Contract::active()
            ->with(['flat', 'tenant'])
            ->whereDate('start_date', '<=', $targetDate->endOfMonth())
            ->whereDate('end_date', '>=', $targetDate->startOfMonth())
            ->get();

        $this->info("📋 {$contracts->count()} contrats actifs trouvés.");

        if ($contracts->isEmpty()) {
            $this->warn("❌ Aucun contrat actif trouvé pour cette période.");
            return 1;
        }

        foreach ($contracts as $contract) {
            // Vérifier si la facture existe déjà
            $exists = Payment::where('contract_id', $contract->id)
                ->where('current_month', $currentMonth)
                ->exists();

            if (!$exists) {
                try {
                    // Créer la facture
                    $payment = Payment::create([
                        'numero' => $this->generatePaymentNumber(),
                        'contract_id' => $contract->id,
                        'tenant_id' => $contract->tenant_id,
                        'flat_id' => $contract->flat_id,
                        'amount' => $contract->monthly_rent,
                        'current_month' => $currentMonth,
                        'due_date' => $targetDate->copy()->day(5), // Échéance le 5 du mois
                        'date_payment' => now() , // Date de paiement initiale
                        'status' => '0', // Non payé
                    ]);

                    $count++;
                    $this->info("✅ Facture {$payment->numero} créée pour contrat {$contract->contract_number} - Locataire: {$contract->tenant->name} - Montant: {$contract->monthly_rent} FCFA");

                } catch (\Exception $e) {
                    $this->error("❌ Erreur pour contrat {$contract->contract_number}: " . $e->getMessage());
                }
            }
        }

        // Résumé final
        if ($count === 0) {
            $this->info("Aucune nouvelle facture générée. Elles existent déjà pour ce mois.");
            return 1;
        }

        $this->info("✅ {$count} facture(s) générée(s) pour le mois de {$currentMonth}.");
        return 0;
    }

    private function generatePaymentNumber(): string
    {
        $year = now()->year;
        $month = now()->format('m');
        $count = Payment::whereYear('created_at', $year)
                       ->whereMonth('created_at', now()->month)
                       ->count() + 1;
                       
        return "FAC-{$year}{$month}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}