<?php

namespace App\Services;

use App\Models\User;
use App\Models\Owner;
use App\Models\Property;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OwnerUserService
{
  /**
   * Trouve ou crée un utilisateur pour un propriétaire
   */
  public function findOrCreateUserForOwner(string $email, string $name, int $agencyId): User
  {
    // Chercher un utilisateur existant avec cet email
    $user = User::where('email', $email)->first();

    if ($user) {
      Log::info("Found existing user for owner creation", [
        'user_id' => $user->id,
        'email' => $email,
        'existing_roles' => $user->roles->pluck('name')->toArray(),
        'agency_id' => $agencyId,
      ]);

      // L'utilisateur existe, vérifier s'il est déjà associé à cette agence
      $existingAssociation = $user->agencys()->where('agency_id', $agencyId)->first();

      if (!$existingAssociation) {
        // Associer l'utilisateur à la nouvelle agence
        $user->agencys()->attach($agencyId, [
          'created_at' => now(),
          'updated_at' => now(),
        ]);
        Log::info("Associated existing user with agency", [
          'user_id' => $user->id,
          'agency_id' => $agencyId,
        ]);
      }

      // S'assurer que l'utilisateur a le rôle owner
      // Vérifier d'abord s'il a déjà un rôle plus élevé (agency-owner, super-admin)
      $hasHigherRole = $user->hasRole('super-admin') || $user->hasRole('agency-owner');

      if (!$user->hasRole('owner') && !$hasHigherRole) {
        $user->assignRole('owner');
        Log::info("Assigned owner role to existing user", [
          'user_id' => $user->id,
          'email' => $email,
        ]);
      } elseif ($hasHigherRole) {
        Log::info("User already has higher role, not assigning owner role", [
          'user_id' => $user->id,
          'email' => $email,
          'existing_roles' => $user->roles->pluck('name')->toArray(),
        ]);
      }

      return $user;
    }

    // L'utilisateur n'existe pas, le créer avec un compte activé
    Log::info("Creating new user for owner", [
      'email' => $email,
      'name' => $name,
      'agency_id' => $agencyId,
    ]);

    $user = User::create([
      'name' => $name,
      'email' => $email,
      'password' => Hash::make('password'), // Mot de passe par défaut
      'email_verified_at' => now(), // Compte activé par défaut
    ]);

    // Assigner le rôle owner
    $user->assignRole('owner');

    // Associer à l'agence
    $user->agencys()->attach($agencyId, [
      'created_at' => now(),
      'updated_at' => now(),
    ]);

    Log::info("Created new user with owner role", [
      'user_id' => $user->id,
      'email' => $email,
      'agency_id' => $agencyId,
    ]);

    return $user;
  }

  /**
   * Crée un propriétaire avec un utilisateur associé
   * Cette méthode est maintenant dépréciée car elle nécessitait property_id
   * Utiliser createOwnerWithAgency() à la place
   */
  public function createOwnerWithUser(array $ownerData, string $email): Owner
  {
    throw new \Exception('Cette méthode est dépréciée. Un owner peut maintenant avoir plusieurs propriétés. Utilisez createOwnerWithAgency() à la place.');
  }

  /**
   * Crée un propriétaire avec un utilisateur associé en utilisant directement l'agency_id
   * Utilisé lors de la création depuis PropertyResource
   */
  public function createOwnerWithAgency(array $ownerData, string $email, int $agencyId): Owner
  {
    return DB::transaction(function () use ($ownerData, $email, $agencyId) {
      // Trouver ou créer l'utilisateur
      $user = $this->findOrCreateUserForOwner($email, $ownerData['name'], $agencyId);

      // Créer le propriétaire
      $owner = Owner::create(array_merge($ownerData, [
        'user_id' => $user->id,
      ]));

      Log::info("Owner created with user account via agency", [
        'owner_id' => $owner->id,
        'user_id' => $user->id,
        'user_email' => $user->email,
        'agency_id' => $agencyId,
      ]);

      return $owner;
    });
  }

  /**
   * Met à jour l'association utilisateur-propriétaire lors d'un changement d'email
   */
  public function updateOwnerUserAssociation(Owner $owner, string $newEmail): User
  {
    return DB::transaction(function () use ($owner, $newEmail) {
      Log::info("Starting owner user association update", [
        'owner_id' => $owner->id,
        'current_user_id' => $owner->user_id,
        'current_email' => $owner->user?->email,
        'new_email' => $newEmail,
      ]);

      // Vérifier si l'email n'a pas vraiment changé
      if ($owner->user && $owner->user->email === $newEmail) {
        Log::info("Email unchanged, returning existing user", [
          'owner_id' => $owner->id,
          'user_id' => $owner->user->id,
          'email' => $newEmail,
        ]);
        return $owner->user;
      }

      // Récupérer l'agence via les propriétés de l'owner ou via l'utilisateur actuel
      $agencyId = null;

      // Essayer d'abord via les propriétés
      $firstProperty = $owner->properties()->first();
      if ($firstProperty) {
        $agencyId = $firstProperty->agency_id;
      } else {
        // Si pas de propriétés, essayer via l'utilisateur actuel
        $currentUser = $owner->user;
        if ($currentUser) {
          $firstAgency = $currentUser->agencys()->first();
          if ($firstAgency) {
            $agencyId = $firstAgency->id;
          }
        }
      }

      // Si on ne trouve toujours pas d'agence, utiliser l'agence courante du tenant
      if (!$agencyId) {
        $currentTenant = \Filament\Facades\Filament::getTenant();
        if ($currentTenant) {
          $agencyId = $currentTenant->id;
        } else {
          throw new \Exception('Unable to determine agency for owner user association');
        }
      }

      // Vérifier si un utilisateur avec ce nouvel email existe déjà
      $existingUser = User::where('email', $newEmail)->first();

      if ($existingUser) {
        Log::info("Found existing user with new email", [
          'existing_user_id' => $existingUser->id,
          'new_email' => $newEmail,
          'existing_roles' => $existingUser->roles->pluck('name')->toArray(),
        ]);

        // Vérifier si cet utilisateur est déjà associé à un autre owner
        $existingOwner = Owner::where('user_id', $existingUser->id)->first();

        if ($existingOwner && $existingOwner->id !== $owner->id) {
          throw new \Exception("L'utilisateur avec l'email {$newEmail} est déjà associé à un autre propriétaire (ID: {$existingOwner->id}). Veuillez utiliser un autre email ou dissocier l'utilisateur de l'autre propriétaire.");
        }

        // S'assurer que l'utilisateur existant est associé à l'agence
        $existingAssociation = $existingUser->agencys()->where('agency_id', $agencyId)->first();
        if (!$existingAssociation) {
          $existingUser->agencys()->attach($agencyId, [
            'created_at' => now(),
            'updated_at' => now(),
          ]);
        }

        // S'assurer que l'utilisateur a le rôle owner (sauf s'il a un rôle plus élevé)
        $hasHigherRole = $existingUser->hasRole('super-admin') || $existingUser->hasRole('agency-owner');
        if (!$existingUser->hasRole('owner') && !$hasHigherRole) {
          $existingUser->assignRole('owner');
        }

        // Mettre à jour l'association
        $owner->update(['user_id' => $existingUser->id]);

        Log::info("Updated owner association to existing user", [
          'owner_id' => $owner->id,
          'user_id' => $existingUser->id,
          'new_email' => $newEmail,
        ]);

        return $existingUser;
      }

      // Aucun utilisateur existant avec ce nouvel email, créer un nouveau
      $newUser = $this->findOrCreateUserForOwner($newEmail, $owner->name, $agencyId);

      // Mettre à jour l'association
      $owner->update(['user_id' => $newUser->id]);

      Log::info("Owner user association updated with new user", [
        'owner_id' => $owner->id,
        'new_user_id' => $newUser->id,
        'new_email' => $newEmail,
        'agency_id' => $agencyId,
      ]);

      return $newUser;
    });
  }
}
