<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\Payment;
use Illuminate\Support\Carbon;

class GenerateMonthlyInvoices extends Command
{
    protected $signature = 'invoices:generate-monthly';
    protected $description = 'Génère les factures mensuelles pour chaque locataire';

   public function handle()
{
    $month = Carbon::now()->format('Y-m');
    $count = 0;

    $tenants = Tenant::with('flatThroughContract')->get();

    foreach ($tenants as $tenant) {
        if (!$tenant->flatThroughContract) continue;

        $exists = Payment::where('tenant_id', $tenant->id)
            ->where('current_month', $month)
            ->exists();

        if (!$exists) {
            Payment::create([
                'numero' => 'FAC-' . random_int(100000, 999999),
                'tenant_id' => $tenant->id,
                'flat_id' => $tenant->flatThroughContract?->id,
                'amount' => $tenant->flatThroughContract?->loyer,
                'current_month' => $month,
                'date_payment' => now(),
                'status' => false,
            ]);

            $count++;
        }
    }

    if ($count === 0) {
        $this->info("Aucune nouvelle facture générée. Elles existent déjà.");
        return 1; // signal d'échec "utile"
    }

    $this->info("✅ $count facture(s) générée(s) pour le mois de $month.");
    return 0;
}

}
