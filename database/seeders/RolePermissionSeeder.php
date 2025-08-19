<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
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
        }

        // Create roles
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $ownerRole = Role::firstOrCreate(['name' => 'owner']);
        $tenantRole = Role::firstOrCreate(['name' => 'tenant']);

        // Assign permissions to roles
        $superAdminRole->givePermissionTo(Permission::all());
        
        $adminRole->givePermissionTo([
            'view_admin_panel',
            'manage_properties',
            'manage_contracts',
            'manage_payments',
            'manage_remittances',
        ]);

        $ownerRole->givePermissionTo([
            'view_owner_panel',
        ]);

        $tenantRole->givePermissionTo([
            'view_tenant_panel',
        ]);

        // Create super-admin user if it doesn't exist
        $superAdmin = User::firstOrCreate(
            ['email' => 'dev@capco.sn'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('dev@1234'),
                'email_verified_at' => now(),
            ]
        );

        // Assign super-admin role
        if (!$superAdmin->hasRole('super-admin')) {
            $superAdmin->assignRole('super-admin');
        }

        $this->command->info('Roles and permissions seeded successfully!');
    }
}