<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Services\TenantCacheManager;
use Illuminate\Console\Command;

class CacheTenantClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:tenant-clear {--agency= : ID de l\'agence spécifique à vider} {--all : Vider le cache de toutes les agences}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vide le cache tenant-aware pour une agence spécifique ou toutes les agences';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $agencyId = $this->option('agency');
        $all = $this->option('all');

        if ($all) {
            // Vider le cache de toutes les agences
            $agencies = Agency::all();
            $this->info("Vidage du cache pour {$agencies->count()} agences...");

            $progressBar = $this->output->createProgressBar($agencies->count());
            $progressBar->start();

            foreach ($agencies as $agency) {
                try {
                    TenantCacheManager::flushTenant($agency->id);
                    $progressBar->advance();
                } catch (\Exception $e) {
                    $this->error("Erreur lors du vidage pour l'agence {$agency->name}: " . $e->getMessage());
                }
            }

            $progressBar->finish();
            $this->newLine();
            $this->info("✅ Cache vidé pour toutes les agences");
        } elseif ($agencyId) {
            // Vider le cache d'une agence spécifique
            $agency = Agency::find($agencyId);

            if (!$agency) {
                $this->error("Agence avec l'ID {$agencyId} non trouvée.");
                return 1;
            }

            $this->info("Vidage du cache pour l'agence: {$agency->name}");

            try {
                TenantCacheManager::flushTenant($agency->id);
                $this->info("✅ Cache vidé pour l'agence {$agency->name}");
            } catch (\Exception $e) {
                $this->error("Erreur lors du vidage: " . $e->getMessage());
                return 1;
            }
        } else {
            $this->error("Vous devez spécifier --agency=ID ou --all");
            $this->info("Exemples:");
            $this->info("  php artisan cache:tenant-clear --agency=1");
            $this->info("  php artisan cache:tenant-clear --all");
            return 1;
        }

        return 0;
    }
}
