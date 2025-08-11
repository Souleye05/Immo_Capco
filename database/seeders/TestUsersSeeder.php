<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Agency;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class TestUsersSeeder extends Seeder
{
  public function run(): void
  {
    // Créer les rôles s'ils n'existent pas
    $roles = ['super-admin', 'agency-owner', 'admin', 'owner', 'tenant'];
    foreach ($roles as $roleName) {
      Role::firstOrCreate(['name' => $roleName]);
    }

    // Créer quelques agences de test
    $agency1 = Agency::firstOrCreate([
      'name' => 'Agence Test 1',
      'slug' => 'agence-test-1'
    ]);

    $agency2 = Agency::firstOrCreate([
      'name' => 'Agence Test 2',
      'slug' => 'agence-test-2'
    ]);

    // 1. Utilisateur SANS RÔLE (pour tester NoValidRoleException)
    $userNoRole = User::firstOrCreate([
      'email' => 'no-role@test.com'
    ], [
      'name' => 'Utilisateur Sans Rôle',
      'password' => bcrypt('password'),
      'email_verified_at' => now()
    ]);
    // Ne pas assigner de rôle ni d'agence

    // 2. Utilisateur AVEC RÔLE mais SANS AGENCE (pour tester NoAgencyAccessException)
    $userNoAgency = User::firstOrCreate([
      'email' => 'no-agency@test.com'
    ], [
      'name' => 'Utilisateur Sans Agence',
      'password' => bcrypt('password'),
      'email_verified_at' => now()
    ]);
    $userNoAgency->assignRole('admin');
    // Ne pas assigner d'agence

    // 3. Utilisateur AVEC UNE SEULE AGENCE (sélection automatique)
    $userOneAgency = User::firstOrCreate([
      'email' => 'one-agency@test.com'
    ], [
      'name' => 'Utilisateur Une Agence',
      'password' => bcrypt('password'),
      'email_verified_at' => now()
    ]);
    $userOneAgency->assignRole('admin');
    $userOneAgency->agencys()->syncWithoutDetaching([$agency1->id]);

    // 4. Utilisateur AVEC PLUSIEURS AGENCES (nécessite sélection)
    $userMultiAgency = User::firstOrCreate([
      'email' => 'multi-agency@test.com'
    ], [
      'name' => 'Utilisateur Multi Agences',
      'password' => bcrypt('password'),
      'email_verified_at' => now()
    ]);
    $userMultiAgency->assignRole('admin');
    $userMultiAgency->agencys()->syncWithoutDetaching([$agency1->id, $agency2->id]);

    // 5. SUPER ADMIN (accès direct)
    $superAdmin = User::firstOrCreate([
      'email' => 'super-admin@test.com'
    ], [
      'name' => 'Super Administrateur',
      'password' => bcrypt('password'),
      'email_verified_at' => now()
    ]);
    $superAdmin->assignRole('super-admin');

    // 6. Utilisateur OWNER (propriétaire)
    $owner = User::firstOrCreate([
      'email' => 'owner@test.com'
    ], [
      'name' => 'Propriétaire Test',
      'password' => bcrypt('password'),
      'email_verified_at' => now()
    ]);
    $owner->assignRole('owner');
    $owner->agencys()->syncWithoutDetaching([$agency1->id]);

    // 7. Utilisateur TENANT (locataire)
    $tenant = User::firstOrCreate([
      'email' => 'tenant@test.com'
    ], [
      'name' => 'Locataire Test',
      'password' => bcrypt('password'),
      'email_verified_at' => now()
    ]);
    $tenant->assignRole('tenant');
    $tenant->agencys()->syncWithoutDetaching([$agency1->id]);

    $this->command->info('✅ Utilisateurs de test créés avec succès !');
    $this->command->table(
      ['Email', 'Nom', 'Rôle', 'Agences', 'Scénario de Test'],
      [
        ['no-role@test.com', 'Sans Rôle', 'Aucun', 'Aucune', 'NoValidRoleException'],
        ['no-agency@test.com', 'Sans Agence', 'admin', 'Aucune', 'NoAgencyAccessException'],
        ['one-agency@test.com', 'Une Agence', 'admin', '1', 'Sélection automatique'],
        ['multi-agency@test.com', 'Multi Agences', 'admin', '2', 'TenantSelectionRequired'],
        ['super-admin@test.com', 'Super Admin', 'super-admin', 'Toutes', 'Accès direct'],
        ['owner@test.com', 'Propriétaire', 'owner', '1', 'Panel owner'],
        ['tenant@test.com', 'Locataire', 'tenant', '1', 'Panel tenant'],
      ]
    );
  }
}
