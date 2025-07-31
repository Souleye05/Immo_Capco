<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for each panel and resource
        $permissions = [
            // Super Admin Panel Permissions
            'access_super_admin_panel',
            'manage_agencies',
            'manage_global_users',
            'manage_system_settings',
            'view_platform_analytics',

            // Agency Owner Panel Permissions (Propriétaire d'agence)
            'access_admin_panel',
            'manage_properties',
            'manage_contracts',
            'manage_payments',
            'manage_tenants',
            'manage_owners',
            'manage_expenses',
            'manage_flats',
            'manage_prospects',
            'manage_remittances',
            'manage_versements',
            'manage_unsolds',
            'manage_prestataires',
            'manage_categorie_depenses',
            'manage_team_users',
            'manage_agency_admins',
            'assign_admin_permissions',
            'view_financial_reports',
            'manage_agency_settings',
            'view_admin_dashboard',
            'view_admin_analytics',

            // Admin Panel Permissions (Employé/Manager)
            'access_admin_panel_limited',
            'view_properties',
            'edit_properties',
            'view_contracts',
            'edit_contracts',
            'view_payments',
            'edit_payments',
            'view_tenants',
            'edit_tenants',
            'view_prospects',
            'edit_prospects',
            'view_basic_reports',

            // Owner Panel Permissions
            'access_owner_panel',
            'view_owned_properties',
            'view_property_contracts',
            'view_property_revenues',
            'view_owner_dashboard',

            // Tenant Panel Permissions
            'access_tenant_panel',
            'view_my_contracts',
            'view_my_payments',
            'view_my_documents',
            'view_tenant_dashboard',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Super Admin Role - Full platform access
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdminRole->givePermissionTo([
            'access_super_admin_panel',
            'manage_agencies',
            'manage_global_users',
            'manage_system_settings',
            'view_platform_analytics',
        ]);

        // Agency Owner Role - Propriétaire d'agence (contrôle total de son agence)
        $agencyOwnerRole = Role::firstOrCreate(['name' => 'agency-owner']);
        $agencyOwnerRole->givePermissionTo([
            'access_admin_panel',
            'manage_properties',
            'manage_contracts',
            'manage_payments',
            'manage_tenants',
            'manage_owners',
            'manage_expenses',
            'manage_flats',
            'manage_prospects',
            'manage_remittances',
            'manage_versements',
            'manage_unsolds',
            'manage_prestataires',
            'manage_categorie_depenses',
            'manage_team_users',
            'manage_agency_admins',
            'assign_admin_permissions',
            'view_financial_reports',
            'manage_agency_settings',
            'view_admin_dashboard',
            'view_admin_analytics',
        ]);

        // Admin Role - Employé/Manager (permissions limitées données par l'agency-owner)
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo([
            'access_admin_panel_limited',
            'view_properties',
            'edit_properties',
            'view_contracts',
            'edit_contracts',
            'view_payments',
            'edit_payments',
            'view_tenants',
            'edit_tenants',
            'view_prospects',
            'edit_prospects',
            'view_basic_reports',
        ]);

        // Owner Role - Property owner access
        $ownerRole = Role::firstOrCreate(['name' => 'owner']);
        $ownerRole->givePermissionTo([
            'access_owner_panel',
            'view_owned_properties',
            'view_property_contracts',
            'view_property_revenues',
            'view_owner_dashboard',
        ]);

        // Tenant Role - Tenant access
        $tenantRole = Role::firstOrCreate(['name' => 'tenant']);
        $tenantRole->givePermissionTo([
            'access_tenant_panel',
            'view_my_contracts',
            'view_my_payments',
            'view_my_documents',
            'view_tenant_dashboard',
        ]);

        // Assign default roles to existing users
        $this->assignDefaultRoles();
    }

    /**
     * Assign default roles to existing users based on their current data
     */
    private function assignDefaultRoles(): void
    {
        // Get all users
        $users = User::all();

        foreach ($users as $user) {
            // Check if user already has roles
            if ($user->roles()->count() > 0) {
                continue;
            }

            // Assign roles based on user's relationships and data

            // Check if user has owned properties (should be owner)
            if ($user->ownedProperties()->exists()) {
                $user->assignRole('owner');
            }

            // Check if user has tenant contracts (should be tenant)
            if ($user->tenantContracts()->exists()) {
                $user->assignRole('tenant');
            }

            // Check if user is associated with agencies (could be agency-owner or admin)
            if ($user->agencys()->exists()) {
                // Par défaut, assigner agency-owner aux utilisateurs associés aux agences
                // L'agency-owner pourra ensuite créer des admins avec des permissions limitées
                $user->assignRole('agency-owner');
            }

            // If no specific role assigned, assign tenant as default
            if ($user->roles()->count() === 0) {
                $user->assignRole('tenant');
            }
        }

        // Create a super admin user if none exists
        $superAdminExists = User::role('super-admin')->exists();
        if (!$superAdminExists) {
            // Find the first user or create one
            $firstUser = User::first();
            if ($firstUser) {
                $firstUser->assignRole('super-admin');
                $this->command->info("Assigned super-admin role to user: {$firstUser->email}");
            }
        }
    }
}
