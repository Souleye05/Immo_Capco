<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FixRolesCommand extends Command
{
    protected $signature = 'fix:roles {--force : Force the operation without confirmation}';
    protected $description = 'Fix roles and permissions tables and create missing data';

    public function handle()
    {
        $this->info('🔧 Vérification et réparation des rôles et permissions...');

        try {
            // Vérifier si les tables existent
            $this->checkTables();
            
            // Créer les rôles et permissions de base
            $this->createRolesAndPermissions();
            
            // Créer l'utilisateur super-admin
            $this->createSuperAdmin();
            
            $this->info('✅ Rôles et permissions réparés avec succès !');
            
        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la réparation : ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function checkTables()
    {
        $tables = ['roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions'];
        
        foreach ($tables as $table) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                $this->error("❌ Table '$table' manquante. Exécutez 'php artisan migrate' d'abord.");
                throw new \Exception("Table $table manquante");
            }
        }
        
        $this->info('✅ Toutes les tables nécessaires existent.');
    }

    private function createRolesAndPermissions()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->info('📝 Création des permissions...');
        
        $permissions = [
            'view_admin_panel',
            'view_owner_panel', 
            'view_tenant_panel',
            'manage_agencies',
            'manage_properties',
            'manage_contracts',
            'manage_payments',
            'manage_remittances',
            'manage_users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
            $this->line("  - Permission '$permission' créée/vérifiée");
        }

        $this->info('👥 Création des rôles...');
        
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $ownerRole = Role::firstOrCreate(['name' => 'owner']);
        $tenantRole = Role::firstOrCreate(['name' => 'tenant']);

        $this->line("  - Rôles créés/vérifiés");

        // Assign permissions to roles
        $superAdminRole->syncPermissions(Permission::all());
        
        $adminRole->syncPermissions([
            'view_admin_panel',
            'manage_properties',
            'manage_contracts', 
            'manage_payments',
            'manage_remittances',
        ]);

        $ownerRole->syncPermissions(['view_owner_panel']);
        $tenantRole->syncPermissions(['view_tenant_panel']);

        $this->info('✅ Permissions assignées aux rôles.');
    }

    private function createSuperAdmin()
    {
        $this->info('👤 Création/vérification de l\'utilisateur super-admin...');
        
        $superAdmin = User::firstOrCreate(
            ['email' => 'dev@capco.sn'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('dev@1234'),
                'email_verified_at' => now(),
            ]
        );

        if (!$superAdmin->hasRole('super-admin')) {
            $superAdmin->assignRole('super-admin');
            $this->info('✅ Rôle super-admin assigné à l\'utilisateur.');
        } else {
            $this->info('✅ L\'utilisateur a déjà le rôle super-admin.');
        }
    }
}