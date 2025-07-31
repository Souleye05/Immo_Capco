<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;
use Illuminate\Support\Carbon;

class FixInvalidCurrentMonth extends Command
{
    protected $signature = 'fix:current-month-format';

    protected $description = 'Corrige les champs current_month mal formatés dans la table payments';

    public function handle(): void
{
    $moisFr = [
        'janvier' => '01',
        'février' => '02',
        'mars' => '03',
        'avril' => '04',
        'mai' => '05',
        'juin' => '06',
        'juillet' => '07',
        'août' => '08',
        'septembre' => '09',
        'octobre' => '10',
        'novembre' => '11',
        'décembre' => '12',
    ];

    $payments = \App\Models\Payment::all();
    $fixed = 0;

    foreach ($payments as $payment) {
        $original = trim($payment->current_month);

        if (empty($original)) continue;

        // Exemple : "Janvier 2025" → "2025-01"
        $parts = explode(' ', mb_strtolower($original));
        if (count($parts) === 2 && isset($moisFr[$parts[0]])) {
            $year = $parts[1];
            $month = $moisFr[$parts[0]];
            $formatted = "$year-$month";

            if ($formatted !== $original) {
                $payment->current_month = $formatted;
                $payment->save();
                $this->info("Corrigé : $original => $formatted (paiement ID: {$payment->id})");
                $fixed++;
            }
        } else {
            $this->warn("Invalide : $original (paiement ID: {$payment->id})");
        }
    }

    $this->info("✔️ $fixed paiements corrigés.");
}

}
