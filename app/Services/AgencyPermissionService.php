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
      'system' => [
        'manage_agencies' => 'Gérer les agences',
        'manage_users' => 'Gérer les utilisateurs',
        'system_administration' => 'Administration système',
      ],
    ];
  }

  /**
   * Obtenir toutes les permissions sous forme de liste plate
   */
  public static function getFlatPermissions(): array
  {
    $permissions = [];
    foreach (self::getAllPermissions() as $categoryPermissions) {
      $permissions = array_merge($permissions, $categoryPermissions);
    }
    return $permissions;
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

  /**
   * Détermine le rôle principal d'un utilisateur pour une agence donnée
   */
  public static function getPrimaryRole(User $user, ?Agency $agency = null): string
  {
    // Super admin : accès global, pas de tenant
    if ($user->hasRole('super-admin')) {
      return 'super_admin';
    }

    // Si pas d'agence spécifiée, utiliser le tenant courant
    if (!$agency && ($tenant = Filament::getTenant())) {
      $agency = $tenant;
    }

    if (!$agency) {
      return 'guest';
    }

    // Vérifier si l'utilisateur appartient à cette agence
    $userAgency = $user->agencys()->where('agency_id', $agency->id)->first();
    if (!$userAgency) {
      // L'utilisateur n'appartient pas à cette agence
      return 'guest';
    }

    // Priorité des rôles Spatie (du plus privilégié au moins privilégié)
    $rolePriority = [
      'agency-owner' => 5,
      'admin' => 4,
      'owner' => 3,
      'tenant' => 2,
      'guest' => 1,
    ];

    $userRoles = $user->getRoleNames()->toArray();
    $highestRole = 'guest';
    $highestPriority = 0;

    // Vérifier les rôles Spatie
    foreach ($userRoles as $role) {
      if (isset($rolePriority[$role]) && $rolePriority[$role] > $highestPriority) {
        $highestRole = $role;
        $highestPriority = $rolePriority[$role];
      }
    }

    // Si pas de rôle Spatie trouvé, vérifier les rôles d'agence
    if ($highestRole === 'guest' && $agency) {
      $agencyRoles = $user->agencyRolesForAgency($agency->id);

      if ($agencyRoles->isNotEmpty()) {
        // Pour les rôles d'agence, déterminer le niveau d'accès
        // Par défaut, les utilisateurs avec des rôles d'agence ont accès au panel admin
        $highestRole = 'admin';

        // Vous pouvez personnaliser cette logique selon vos besoins
        // Par exemple, certains rôles d'agence pourraient avoir accès au panel owner
        foreach ($agencyRoles as $agencyRole) {
          if (
            str_contains(strtolower($agencyRole->name), 'propriétaire') ||
            str_contains(strtolower($agencyRole->name), 'owner')
          ) {
            $highestRole = 'owner';
            break;
          }
        }
      }
    }

    // Normaliser les noms de rôles pour la compatibilité
    return match ($highestRole) {
      'agency-owner' => 'admin',
      'super-admin' => 'super_admin',
      default => $highestRole,
    };
  }

  /**
   * Vérifie si un utilisateur peut accéder à un panel spécifique
   */
  public static function canAccessPanel(User $user, string $panelId, ?Agency $agency = null): bool
  {
    $role = self::getPrimaryRole($user, $agency);

    return match ($panelId) {
      'super-admin' => $role === 'super_admin',
      'admin' => in_array($role, ['super_admin', 'admin']),
      'owner' => in_array($role, ['super_admin', 'admin', 'owner']),
      'tenant' => in_array($role, ['super_admin', 'admin', 'owner', 'tenant']),
      'redirection', 'login' => true, // Tout le monde peut accéder à la page de login
      default => false,
    };
  }

  /**
   * Retourne la liste des agences accessibles pour un utilisateur
   */
  public static function getAccessibleAgencies(User $user): \Illuminate\Database\Eloquent\Collection
  {
    if ($user->hasRole('super-admin')) {
      return Agency::all();
    }

    return $user->agencys;
  }

  /**
   * Retourne l'URL du panel approprié pour un utilisateur et une agence
   */
  public static function getPanelUrl(User $user, Agency $agency): string
  {
    $role = self::getPrimaryRole($user, $agency);

    return match ($role) {
      'super_admin' => route('filament.super-admin.pages.dashboard'),
      'admin' => route('filament.admin.pages.dashboard', ['tenant' => $agency->slug]),
      'owner' => route('filament.owner.pages.dashboard', ['tenant' => $agency->slug]),
      'tenant' => route('filament.tenant.pages.dashboard', ['tenant' => $agency->slug]),
      default => route('login'),
    };
  }

  /**
   * Retourne les permissions spécifiques d'un utilisateur pour une agence
   * Basé sur le rôle principal de l'utilisateur
   */
  public static function getRoleBasedPermissions(User $user, ?Agency $agency = null): array
  {
    $role = self::getPrimaryRole($user, $agency);

    return match ($role) {
      'super_admin' => [
        'manage_agencies',
        'manage_users',
        'system_administration',
        // Toutes les autres permissions
        ...array_keys(self::getFlatPermissions())
      ],
      'admin' => [
        'manage_agency_roles',
        'view_team_users',
        'view_properties',
        'create_properties',
        'edit_properties',
        'delete_properties',
        'view_contracts',
        'create_contracts',
        'edit_contracts',
        'delete_contracts',
        'view_tenants',
        'create_tenants',
        'edit_tenants',
        'delete_tenants',
        'view_owners',
        'create_owners',
        'edit_owners',
        'delete_owners',
        'view_payments',
        'create_payments',
        'edit_payments',
        'delete_payments',
        'view_expenses',
        'create_expenses',
        'edit_expenses',
        'delete_expenses',
        'view_financial_reports',
        'manage_remittances',
        'view_analytics',
        'view_prospects',
        'edit_prospects',
        'manage_unsolds',
      ],
      'owner' => [
        'view_properties',
        'view_contracts',
        'view_tenants',
        'view_payments',
        'view_financial_reports',
        'view_analytics',
      ],
      'tenant' => [
        'view_contracts',
        'view_payments',
      ],
      default => [],
    };
  }

  /**
   * Obtenir toutes les permissions d'un utilisateur dans l'agence courante
   * Version améliorée qui combine les permissions basées sur les rôles et les permissions granulaires
   */
  public static function getUserPermissions(User $user, ?Agency $agency = null): array
  {
    // Permissions basées sur le rôle principal
    $rolePermissions = self::getRoleBasedPermissions($user, $agency);

    // Agency-owner et Super-admin ont toutes les permissions
    if ($user->hasRole('agency-owner') || $user->hasRole('super-admin')) {
      return array_keys(self::getFlatPermissions());
    }

    // Permissions granulaires spécifiques à l'agence
    $granularPermissions = [];
    $targetAgency = $agency ?: Filament::getTenant();

    if ($targetAgency) {
      $agencyRoles = $user->agencyRolesForAgency($targetAgency->id);

      foreach ($agencyRoles as $role) {
        $granularPermissions = array_merge($granularPermissions, $role->permissions ?? []);
      }
    }

    // Combiner les permissions basées sur les rôles et les permissions granulaires
    return array_unique(array_merge($rolePermissions, $granularPermissions));
  }

  /**
   * Vérifier si un utilisateur a une permission spécifique dans l'agence courante
   * Version améliorée qui prend en compte les rôles et les permissions granulaires
   */
  public static function userHasPermission(User $user, string $permission, ?Agency $agency = null): bool
  {
    // Agency-owner a tous les droits
    if ($user->hasRole('agency-owner')) {
      return true;
    }

    // Super-admin a tous les droits
    if ($user->hasRole('super-admin')) {
      return true;
    }

    // Vérifier les permissions basées sur le rôle
    $rolePermissions = self::getRoleBasedPermissions($user, $agency);
    if (in_array($permission, $rolePermissions)) {
      return true;
    }

    // Vérifier les permissions granulaires d'agence
    $targetAgency = $agency ?: Filament::getTenant();
    if ($targetAgency) {
      return $user->hasAgencyPermission($permission, $targetAgency->id);
    }

    return false;
  }
}
