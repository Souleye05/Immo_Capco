<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create-user 
                            {--email= : Email de l\'utilisateur}
                            {--name= : Nom de l\'utilisateur}
                            {--password= : Mot de passe (optionnel)}
                            {--role=super-admin : Rôle à assigner}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Créer un utilisateur administrateur avec un rôle spécifique';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Vérifier que les rôles existent
        $this->ensureRolesExist();

        // Récupérer les informations
        $email = $this->option('email') ?: $this->ask('Email de l\'utilisateur');
        $name = $this->option('name') ?: $this->ask('Nom de l\'utilisateur');
        $password = $this->option('password') ?: $this->secret('Mot de passe (laissez vide pour "password")') ?: 'password';
        $role = $this->option('role');

        // Vérifier que le rôle existe
        if (!Role::where('name', $role)->exists()) {
            $this->error("Le rôle '{$role}' n'existe pas.");
            $availableRoles = Role::pluck('name')->toArray();
            $this->info('Rôles disponibles: ' . implode(', ', $availableRoles));
            return 1;
        }

        // Créer ou mettre à jour l'utilisateur
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        // Assigner le rôle
        $user->syncRoles([$role]);

        $this->info("✅ Utilisateur créé avec succès !");
        $this->table(
            ['Champ', 'Valeur'],
            [
                ['Email', $user->email],
                ['Nom', $user->name],
                ['Rôle', $role],
                ['Mot de passe', $password === 'password' ? 'password (par défaut)' : '[personnalisé]'],
            ]
        );

        return 0;
    }

    /**
     * S'assurer que les rôles de base existent
     */
    private function ensureRolesExist(): void
    {
        $roles = ['super-admin', 'agency-owner', 'admin', 'owner', 'tenant'];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }
    }
}
