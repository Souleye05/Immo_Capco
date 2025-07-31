<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Services\TenantCacheManager;
use Illuminate\Console\Command;
use Filament\Facades\Filament;

class CacheTenantWarmup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:tenant-warmup {--agency= : ID de l\'agence spécifique à préchauffer}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Préchauffe le cache tenant-aware pour toutes les agences ou une agence spécifique';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $agencyId = $this->option('agency');

        if ($agencyId) {
            // Préchauffer une agence spécifique
            $agency = Agency::find($agencyId);

            if (!$agency) {
                $this->error("Agence avec l'ID {$agencyId} non trouvée.");
                return 1;
            }

            $this->info("Préchauffage du cache pour l'agence: {$agency->name}");
            $this->warmupAgencyCache($agency);
            $this->info("✅ Cache préchauffé pour l'agence {$agency->name}");
        } else {
            // Préchauffer toutes les agences
            $agencies = Agency::all();
            $this->info("Préchauffage du cache pour {$agencies->count()} agences...");

            $progressBar = $this->output->createProgressBar($agencies->count());
            $progressBar->start();

            foreach ($agencies as $agency) {
                $this->warmupAgencyCache($agency);
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();
            $this->info("✅ Cache préchauffé pour toutes les agences");
        }

        return 0;
    }

    /**
     * Préchauffe le cache pour une agence spécifique
     */
    private function warmupAgencyCache(Agency $agency): void
    {
        try {
            // Préchauffer le cache directement avec l'ID de l'agence
            // Pas besoin de Filament::setTenant() dans une commande console
            TenantCacheManager::warmupCache($agency->id);
        } catch (\Exception $e) {
            $this->error("Erreur lors du préchauffage pour l'agence {$agency->name}: " . $e->getMessage());
        }
    }
}
