<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserWithRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Vider le cache des permissions pour éviter les erreurs
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Créer des utilisateurs avec des rôles spécifiques
        $users = [
            [
                'name' => 'Admin Principal',
                'email' => 'admin.principal@test.com',
                'password' => bcrypt('password'),
                'roles' => ['admin']
            ],
            [
                'name' => 'Propriétaire Test',
                'email' => 'proprietaire@test.com',
                'password' => bcrypt('password'),
                'roles' => ['owner']
            ],
            [
                'name' => 'Locataire Test',
                'email' => 'locataire@test.com',
                'password' => bcrypt('password'),
                'roles' => ['tenant']
            ],
            [
                'name' => 'Multi-rôle',
                'email' => 'multi@test.com',
                'password' => bcrypt('password'),
                'roles' => ['admin', 'owner']
            ]
        ];

        foreach ($users as $userData) {
            $roles = $userData['roles'];
            unset($userData['roles']);

            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            $user->assignRole($roles);

            $this->command->info("Utilisateur créé: {$user->email} avec rôles: " . implode(', ', $roles));
        }
    }
}
