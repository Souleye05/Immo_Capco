<?php

namespace App\Traits;

use App\Scopes\TenantScope;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

trait TenantScoped
{
  /**
   * Boot the tenant scoped trait for a model.
   */
  protected static function bootTenantScoped(): void
  {
    // Add the global scope to automatically filter by tenant
    static::addGlobalScope(new TenantScope);

    // Automatically assign the tenant ID when creating new records
    static::creating(function (Model $model) {
      if (Filament::getTenant() && !$model->getAttribute($model->getTenantKeyName())) {
        $model->setAttribute($model->getTenantKeyName(), Filament::getTenant()->getKey());
      }
    });
  }

  /**
   * Determine if the model should be scoped by tenant.
   */
  public function isTenantScoped(): bool
  {
    return true;
  }

  /**
   * Get the name of the tenant key column.
   */
  public function getTenantKeyName(): string
  {
    return 'agency_id';
  }

  /**
   * Get the tenant relationship.
   */
  public function agency()
  {
    return $this->belongsTo(\App\Models\Agency::class, $this->getTenantKeyName());
  }

  /**
   * Scope a query to exclude tenant filtering (for admin purposes).
   */
  public function scopeWithoutTenantScope($query)
  {
    return $query->withoutGlobalScope(TenantScope::class);
  }

  /**
   * Scope a query to a specific tenant.
   */
  public function scopeForTenant($query, $tenantId)
  {
    return $query->withoutGlobalScope(TenantScope::class)
      ->where($this->getTenantKeyName(), $tenantId);
  }
}
