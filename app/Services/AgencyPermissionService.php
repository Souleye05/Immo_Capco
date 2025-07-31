<?php

namespace App\Services;

use App\Models\User;
use App\Models\Agency;
use Filament\Facades\Filament;

class AgencyPermissionService
{
  /**
   * Définition de toutes les permissions disponibles par ressource
   */
  public static function getAllPermissions(): array
  {
    return [
      'properties' => [
        'view_properties' => 'Voir les propriétés',
        'create_properties' => 'Créer des propriétés',
        'edit_properties' => 'Modifier les propriétés',
        'delete_properties' => 'Supprimer les propriétés',
      ],
      'contracts' => [
        'view_contracts' => 'Voir les contrats',
        'create_contracts' => 'Créer des contrats',
        'edit_contracts' => 'Modifier les contrats',
        'delete_contracts' => 'Supprimer des contrats',
      ],
      'payments' => [
        'view_payments' => 'Voir les paiements',
        'create_payments' => 'Créer des paiements',
        'edit_payments' => 'Modifier les paiements',
        'delete_payments' => 'Supprimer des paiements',
      ],
      'tenants' => [
        'view_tenants' => 'Voir les locataires',
        'create_tenants' => 'Créer des locataires',
        'edit_tenants' => 'Modifier les locataires',
        'delete_tenants' => 'Supprimer des locataires',
      ],
      'owners' => [
        'view_owners' => 'Voir les propriétaires',
        'create_owners' => 'Créer des propriétaires',
        'edit_owners' => 'Modifier les propriétaires',
        'delete_owners' => 'Supprimer des propriétaires',
      ],
      'expenses' => [
        'view_expenses' => 'Voir les dépenses',
        'create_expenses' => 'Créer des dépenses',
        'edit_expenses' => 'Modifier les dépenses',
        'delete_expenses' => 'Supprimer des dépenses',
      ],
      'financial' => [
        'view_financial_reports' => 'Voir les rapports financiers',
        'manage_remittances' => 'Gérer les virements',
        'view_analytics' => 'Voir les statistiques',
      ],
      'commercial' => [
        'view_prospects' => 'Voir les prospects',
        'edit_prospects' => 'Modifier les prospects',
        'manage_unsolds' => 'Gérer les biens non loués',
      ],
      'team' => [
        'view_team_users' => 'Voir l\'équipe',
        'manage_agency_roles' => 'Gérer les rôles d\'équipe',
      ],
    ];
  }

  /**
   * Obtenir toutes les permissions sous forme de liste plate
   */
  public static function getFlatPermissions(): array
  {
    $permissions = [];
    foreach (self::getAllPermissions() as $category => $categoryPermissions) {
      $permissions = array_merge($permissions, $categoryPermissions);
    }
    return $permissions;
  }

  /**
   * Vérifier si un utilisateur a une permission spécifique dans l'agence courante
   */
  public static function userHasPermission(User $user, string $permission): bool
  {
    // Agency-owner a tous les droits
    if ($user->hasRole('agency-owner')) {
      return true;
    }

    // Super-admin a tous les droits
    if ($user->hasRole('super-admin')) {
      return true;
    }

    // Vérifier les permissions d'agence
    if ($tenant = Filament::getTenant()) {
      return $user->hasAgencyPermission($permission, $tenant->id);
    }

    return false;
  }

  /**
   * Obtenir toutes les permissions d'un utilisateur dans l'agence courante
   */
  public static function getUserPermissions(User $user): array
  {
    // Agency-owner a toutes les permissions
    if ($user->hasRole('agency-owner')) {
      return array_keys(self::getFlatPermissions());
    }

    // Super-admin a toutes les permissions
    if ($user->hasRole('super-admin')) {
      return array_keys(self::getFlatPermissions());
    }

    $permissions = [];
    if ($tenant = Filament::getTenant()) {
      $agencyRoles = $user->agencyRolesForAgency($tenant->id);

      foreach ($agencyRoles as $role) {
        $permissions = array_merge($permissions, $role->permissions ?? []);
      }
    }

    return array_unique($permissions);
  }

  /**
   * Vérifier si un utilisateur peut accéder à une ressource
   */
  public static function canAccessResource(User $user, string $resourceClass): bool
  {
    // Mapping des ressources vers leurs permissions de base
    $resourcePermissions = [
      'PropertyResource' => 'view_properties',
      'ContractResource' => 'view_contracts',
      'PaymentResource' => 'view_payments',
      'TenantResource' => 'view_tenants',
      'OwnerResource' => 'view_owners',
      'ExpenseResource' => 'view_expenses',
      'AgencyRoleResource' => 'manage_agency_roles',
      'UserResource' => 'view_team_users',
    ];

    $resourceName = class_basename($resourceClass);
    $permission = $resourcePermissions[$resourceName] ?? null;

    if (!$permission) {
      return false;
    }

    return self::userHasPermission($user, $permission);
  }
}
