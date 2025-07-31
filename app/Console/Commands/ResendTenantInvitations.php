<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TenantUserService;
use Illuminate\Console\Command;

class ResendTenantInvitations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:resend-invitations 
                            {--email= : Renvoyer l\'invitation pour un email spécifique}
                            {--agency= : Renvoyer les invitations pour une agence spécifique}
                            {--all : Renvoyer toutes les invitations en attente}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Renvoie les invitations aux locataires qui n\'ont pas encore activé leur compte';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantUserService = app(TenantUserService::class);

        $email = $this->option('email');
        $agencyId = $this->option('agency');
        $all = $this->option('all');

        if (!$email && !$agencyId && !$all) {
            $this->error('Vous devez spécifier --email, --agency ou --all');
            return 1;
        }

        // Construire la requête pour les utilisateurs non activés
        $query = User::whereNull('email_verified_at')
            ->whereHas('tenants'); // Seulement les utilisateurs qui sont des locataires

        if ($email) {
            $query->where('email', $email);
        }

        if ($agencyId) {
            $query->whereHas('agencys', function ($q) use ($agencyId) {
                $q->where('agency_id', $agencyId);
            });
        }

        $users = $query->with('agencys')->get();

        if ($users->isEmpty()) {
            $this->info('Aucun locataire trouvé avec les critères spécifiés.');
            return 0;
        }

        $this->info("Trouvé {$users->count()} locataire(s) non activé(s).");

        $sent = 0;
        $errors = 0;

        foreach ($users as $user) {
            try {
                // Pour chaque agence associée à l'utilisateur
                foreach ($user->agencys as $agency) {
                    $tenantUserService->resendTenantInvitation($user, $agency->id);
                    $this->line("✅ Invitation renvoyée à {$user->email} pour l'agence {$agency->name}");
                    $sent++;
                }
            } catch (\Exception $e) {
                $this->error("❌ Erreur pour {$user->email}: {$e->getMessage()}");
                $errors++;
            }
        }

        $this->info("Résumé: {$sent} invitation(s) renvoyée(s), {$errors} erreur(s).");

        return 0;
    }
}
