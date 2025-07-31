<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Filament\Facades\Filament;

trait HasAgencyPermissions
{
  /**
   * Vérifier si l'utilisateur a une permission d'agence spécifique
   */
  protected static function hasAgencyPermission(string $permission): bool
  {
    $user = auth()->user();

    if (!$user) {
      return false;
    }

    // Agency-owner a tous les droits
    if ($user->hasRole('agency-owner')) {
      return true;
    }

    // Vérifier les permissions d'agence
    if ($tenant = Filament::getTenant()) {
      return $user->hasAgencyPermission($permission, $tenant->id);
    }

    return false;
  }

  /**
   * Permissions pour voir la ressource
   */
  public static function canViewAny(): bool
  {
    return static::hasAgencyPermission(static::getViewPermission());
  }

  /**
   * Permissions pour créer
   */
  public static function canCreate(): bool
  {
    return static::hasAgencyPermission(static::getCreatePermission());
  }

  /**
   * Permissions pour modifier
   */
  public static function canEdit(Model $record): bool
  {
    return static::hasAgencyPermission(static::getEditPermission());
  }

  /**
   * Permissions pour supprimer
   */
  public static function canDelete(Model $record): bool
  {
    return static::hasAgencyPermission(static::getDeletePermission());
  }

  /**
   * Permissions pour voir un enregistrement
   */
  public static function canView(Model $record): bool
  {
    return static::hasAgencyPermission(static::getViewPermission());
  }

  // Méthodes à implémenter dans chaque ressource
  abstract protected static function getViewPermission(): string;
  abstract protected static function getCreatePermission(): string;
  abstract protected static function getEditPermission(): string;
  abstract protected static function getDeletePermission(): string;
}
