<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Filament\Facades\Filament;

class TenantScope implements Scope
{
  /**
   * Apply the scope to a given Eloquent query builder.
   */
  public function apply(Builder $builder, Model $model): void
  {
    // Only apply tenant scoping if we have a current tenant and the model supports tenant scoping
    if (Filament::getTenant() && method_exists($model, 'isTenantScoped') && $model->isTenantScoped()) {
      $builder->where($model->getTenantKeyName(), Filament::getTenant()->getKey());
    }
  }
}
