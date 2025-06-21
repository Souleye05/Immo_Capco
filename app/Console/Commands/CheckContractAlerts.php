<?php

namespace App\Console\Commands;
use App\Models\Payment;
use App\Models\Contract;
use Illuminate\Console\Command;

class CheckContractAlerts extends Command
{
    protected $signature = 'contracts:check-alerts {--days=}';
    protected $description = 'Vérifier les contrats nécessitant une alerte';

    public function handle()
    {
        $days = $this->option('days') ?? null;
        
        // Contrats qui vont expirer bientôt
        $expiringContracts = Contract::active()
            ->with(['tenant', 'flat'])
            ->get()
            ->filter(fn($contract) => $contract->shouldAlert());
            
        // Contrats déjà expirés
        $expiredContracts = Contract::active()
            ->with(['tenant', 'flat'])
            ->get()
            ->filter(fn($contract) => $contract->is_expired);
        
        $this->info("🔍 Vérification des alertes de contrats...");
        
        // Traiter les contrats qui expirent bientôt
        if ($expiringContracts->isNotEmpty()) {
            $this->warn("⚠️  CONTRATS EXPIRANT BIENTÔT:");
            foreach ($expiringContracts as $contract) {
                $daysUntil = abs($contract->days_until_expiration);
                $this->warn("   📅 Contrat {$contract->contract_number} - {$contract->tenant->name} - Expire dans {$daysUntil} jours ({$contract->end_date->format('d/m/Y')})");
                
                // Ici vous pouvez ajouter votre logique de notification
                // $this->sendExpiringNotification($contract);
            }
        }
        
        // Traiter les contrats expirés
        if ($expiredContracts->isNotEmpty()) {
            $this->error("🚨 CONTRATS EXPIRÉS:");
            foreach ($expiredContracts as $contract) {
                $daysExpired = abs($contract->days_until_expiration);
                $this->error("   ❌ Contrat {$contract->contract_number} - {$contract->tenant->name} - Expiré depuis {$daysExpired} jours ({$contract->end_date->format('d/m/Y')})");
                
                // Optionnel: Changer automatiquement le statut
                // $contract->update(['status' => 'expired']);
            }
        }
        
        $totalAlerts = $expiringContracts->count() + $expiredContracts->count();
        
        if ($totalAlerts === 0) {
            $this->info("✅ Aucune alerte. Tous les contrats sont en ordre.");
        } else {
            $this->info("📊 RÉSUMÉ: {$totalAlerts} alerte(s) générée(s)");
            $this->info("   ⚠️  {$expiringContracts->count()} contrat(s) expirant bientôt");
            $this->info("   ❌ {$expiredContracts->count()} contrat(s) expirés");
        }
        
        return 0;
    }
    
    private function sendExpiringNotification($contract)
    {
        // Implémentez votre logique de notification ici
        // Exemple: Email, SMS, notification push, etc.
    }
}