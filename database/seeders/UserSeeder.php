<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // SUPER ADMIN
        User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
            ]
        )->assignRole('super-admin');

        // AGENCY OWNER
        $agencyOwner = User::firstOrCreate(
            ['email' => 'owner@agency.com'],
            [
                'name' => 'Agency Owner',
                'password' => Hash::make('password'),
            ]
        );
        $agencyOwner->assignRole('agency-owner');

        // ADMIN (Employé)
        $admin = User::firstOrCreate(
            ['email' => 'admin@agency.com'],
            [
                'name' => 'Admin Employé',
                'password' => Hash::make('password'),
            ]
        );
        $admin->assignRole('admin');

        // OWNER (Propriétaire)
        $owner = User::firstOrCreate(
            ['email' => 'proprietaire@immo.com'],
            [
                'name' => 'Propriétaire Client',
                'password' => Hash::make('password'),
            ]
        );
        $owner->assignRole('owner');

        // TENANT (Locataire)
        $tenant = User::firstOrCreate(
            ['email' => 'locataire@immo.com'],
            [
                'name' => 'Locataire Test',
                'password' => Hash::make('password'),
            ]
        );
        $tenant->assignRole('tenant');
    }
}
