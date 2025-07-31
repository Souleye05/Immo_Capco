<?php

namespace App\Services;

use App\Models\User;
use App\Models\Owner;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

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
      // L'utilisateur existe, vérifier s'il est déjà associé à cette agence
      $this->associateUserWithAgency($user, $agencyId);

      // S'assurer que l'utilisateur a le rôle owner
      $this->assignOwnerRole($user);

      return $user;
    }

    // L'utilisateur n'existe pas, le créer
    $user = User::create([
      'name' => $name,
      'email' => $email,
      'password' => Hash::make('password'), // Mot de passe par défaut
      'email_verified_at' => now(), // Compte activé par défaut
    ]);

    // Assigner le rôle owner
    $this->assignOwnerRole($user);

    // Associer à l'agence
    $this->associateUserWithAgency($user, $agencyId);

    return $user;
  }

  /**
   * Crée un propriétaire avec un utilisateur associé
   */
  public function createOwnerWithUser(array $ownerData, string $email): Owner
  {
    // Récupérer l'agence via la propriété
    $propertyId = $ownerData['property_id'] ?? null;
    if (!$propertyId) {
      throw new \Exception('Property ID is required to determine agency association');
    }

    $property = \App\Models\Property::find($propertyId);
    if (!$property) {
      throw new \Exception('Property not found');
    }

    $agencyId = $property->agency_id;

    return DB::transaction(function () use ($ownerData, $email, $agencyId) {
      // Trouver ou créer l'utilisateur
      $user = $this->findOrCreateUserForOwner($email, $ownerData['name'], $agencyId);

      // Créer le propriétaire
      $owner = Owner::create(array_merge($ownerData, [
        'user_id' => $user->id,
      ]));

      return $owner;
    });
  }

  /**
   * Met à jour l'association utilisateur-propriétaire lors d'un changement d'email
   */
  public function updateOwnerUserAssociation(Owner $owner, string $newEmail): User
  {
    // Récupérer l'agence via la propriété
    $property = $owner->property;
    if (!$property) {
      throw new \Exception('Owner must have an associated property to determine agency');
    }

    $agencyId = $property->agency_id;

    return DB::transaction(function () use ($owner, $newEmail, $agencyId) {
      // Trouver ou créer l'utilisateur avec le nouvel email
      $user = $this->findOrCreateUserForOwner($newEmail, $owner->name, $agencyId);

      // Mettre à jour l'association
      $owner->update(['user_id' => $user->id]);

      return $user;
    });
  }

  /**
   * Assigne le rôle 'owner' à un utilisateur
   */
  private function assignOwnerRole(User $user): void
  {
    if (!$user->hasRole('owner')) {
      $user->assignRole('owner');
    }
  }

  /**
   * Associe un utilisateur à une agence
   */
  private function associateUserWithAgency(User $user, int $agencyId): void
  {
    // Vérifier si l'utilisateur est déjà associé à cette agence
    $existingAssociation = $user->agencys()->where('agency_id', $agencyId)->first();

    if (!$existingAssociation) {
      // Associer l'utilisateur à l'agence
      $user->agencys()->attach($agencyId, [
        'created_at' => now(),
        'updated_at' => now(),
      ]);
    }
  }
}
