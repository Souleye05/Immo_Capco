<?php

// 1. COMMAND POUR VÉRIFIER LES RENOUVELLEMENTS (à exécuter via CRON)
namespace App\Console\Commands;

use App\Models\Contract;
use App\Services\ContractService;
use App\Notifications\ContractExpirationNotification;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CheckContractRenewals extends Command
{
    protected $signature = 'contracts:check-renewals';
    protected $description = 'Vérifie les contrats à renouveler et envoie les notifications';

    public function handle()
    {
        $contractService = app(ContractService::class);
        
        // Contrats expirant dans 90 jours (alerte précoce)
        $contractsExpiringSoon = Contract::active()
            ->where('end_date', '<=', Carbon::now()->addDays(90))
            ->where('end_date', '>', Carbon::now()->addDays(30))
            ->get();

        foreach ($contractsExpiringSoon as $contract) {
            $contractService->sendExpirationNotification($contract, 'early_warning');
        }

        // Contrats expirant dans 30 jours (alerte finale)
        $contractsExpiringNext = Contract::active()
            ->where('end_date', '<=', Carbon::now()->addDays(30))
            ->where('end_date', '>', Carbon::now())
            ->get();

        foreach ($contractsExpiringNext as $contract) {
            $contractService->sendExpirationNotification($contract, 'final_warning');
        }

        // Renouvellements automatiques
        $contractsToAutoRenew = Contract::active()
            ->where('auto_renewal', true)
            ->where('end_date', '<=', Carbon::now())
            ->get();

        foreach ($contractsToAutoRenew as $contract) {
            $contractService->autoRenewContract($contract);
        }

        $this->info('Vérification des renouvellements terminée');
    }
}