<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Filament\Facades\Filament;

class TenantCacheManager
{
  /**
   * Préfixe pour toutes les clés de cache tenant-aware
   */
  private const TENANT_PREFIX = 'tenant';

  /**
   * Durée par défaut du cache (en secondes)
   */
  private const DEFAULT_TTL = 3600; // 1 heure

  /**
   * Génère une clé de cache tenant-aware
   */
  public static function key(string $key, ?int $agencyId = null): string
  {
    $agencyId = $agencyId ?? self::getCurrentAgencyId();

    if (!$agencyId) {
      throw new \Exception('No agency context available for tenant cache');
    }

    return self::TENANT_PREFIX . ":{$agencyId}:{$key}";
  }

  /**
   * Récupère une valeur du cache tenant-aware
   */
  public static function get(string $key, $default = null, ?int $agencyId = null)
  {
    $tenantKey = self::key($key, $agencyId);
    return Cache::get($tenantKey, $default);
  }

  /**
   * Stocke une valeur dans le cache tenant-aware
   */
  public static function put(string $key, $value, ?int $ttl = null, ?int $agencyId = null): bool
  {
    $tenantKey = self::key($key, $agencyId);
    $ttl = $ttl ?? self::DEFAULT_TTL;

    return Cache::put($tenantKey, $value, $ttl);
  }

  /**
   * Stocke une valeur dans le cache pour toujours (jusqu'à invalidation manuelle)
   */
  public static function forever(string $key, $value, ?int $agencyId = null): bool
  {
    $tenantKey = self::key($key, $agencyId);
    return Cache::forever($tenantKey, $value);
  }

  /**
   * Récupère ou calcule une valeur avec mise en cache
   */
  public static function remember(string $key, \Closure $callback, ?int $ttl = null, ?int $agencyId = null)
  {
    $tenantKey = self::key($key, $agencyId);
    $ttl = $ttl ?? self::DEFAULT_TTL;

    return Cache::remember($tenantKey, $ttl, $callback);
  }

  /**
   * Supprime une clé du cache tenant-aware
   */
  public static function forget(string $key, ?int $agencyId = null): bool
  {
    $tenantKey = self::key($key, $agencyId);
    return Cache::forget($tenantKey);
  }

  /**
   * Vide tout le cache d'une agence
   */
  public static function flushTenant(?int $agencyId = null): void
  {
    $agencyId = $agencyId ?? self::getCurrentAgencyId();

    if (!$agencyId) {
      throw new \Exception('No agency context available for cache flush');
    }

    $pattern = self::TENANT_PREFIX . ":{$agencyId}:*";

    // Pour Redis
    if (config('cache.default') === 'redis') {
      $keys = Cache::getRedis()->keys($pattern);
      if (!empty($keys)) {
        Cache::getRedis()->del($keys);
      }
    } else {
      // Pour les autres drivers, on utilise une approche différente
      // Stocker les clés dans un set pour pouvoir les supprimer
      $keysSetKey = self::TENANT_PREFIX . ":{$agencyId}:_keys_set";
      $keys = Cache::get($keysSetKey, []);

      foreach ($keys as $key) {
        Cache::forget($key);
      }

      Cache::forget($keysSetKey);
    }
  }

  /**
   * Ajoute une clé au set de clés trackées (pour les drivers non-Redis)
   */
  private static function trackKey(string $tenantKey, ?int $agencyId = null): void
  {
    if (config('cache.default') !== 'redis') {
      $agencyId = $agencyId ?? self::getCurrentAgencyId();
      $keysSetKey = self::TENANT_PREFIX . ":{$agencyId}:_keys_set";

      $keys = Cache::get($keysSetKey, []);
      $keys[] = $tenantKey;
      $keys = array_unique($keys);

      Cache::forever($keysSetKey, $keys);
    }
  }

  /**
   * Récupère l'ID de l'agence courante
   */
  private static function getCurrentAgencyId(): ?int
  {
    $tenant = Filament::getTenant();
    return $tenant ? $tenant->id : null;
  }

  /**
   * Cache les statistiques d'une agence
   */
  public static function cacheAgencyStats(?int $agencyId = null): array
  {
    return self::remember('agency_stats', function () use ($agencyId) {
      $agencyId = $agencyId ?? self::getCurrentAgencyId();

      return [
        'properties_count' => \App\Models\Property::count(),
        'contracts_count' => \App\Models\Contract::count(),
        'active_contracts_count' => \App\Models\Contract::where('status', 'active')->count(),
        'payments_count' => \App\Models\Payment::count(),
        'tenants_count' => \App\Models\Tenant::count(),
        'total_revenue' => \App\Models\Payment::where('status', true)->sum('amount'),
        'pending_payments' => \App\Models\Payment::where('status', false)->sum('amount'),
        'last_updated' => now()->toISOString(),
      ];
    }, 1800, $agencyId); // Cache pour 30 minutes
  }

  /**
   * Cache les propriétés d'un owner
   */
  public static function cacheOwnerProperties(int $ownerId, ?int $agencyId = null): \Illuminate\Database\Eloquent\Collection
  {
    return self::remember("owner_properties:{$ownerId}", function () use ($ownerId) {
      return \App\Models\Property::where('owner_id', $ownerId)
        ->with(['flats', 'contracts.tenant'])
        ->get();
    }, 3600, $agencyId); // Cache pour 1 heure
  }

  /**
   * Cache les revenus d'un owner
   */
  public static function cacheOwnerRevenue(int $ownerId, ?int $agencyId = null): array
  {
    return self::remember("owner_revenue:{$ownerId}", function () use ($ownerId) {
      $properties = \App\Models\Property::where('owner_id', $ownerId)->pluck('id');

      $totalRevenue = \App\Models\Payment::whereHas('flat', function ($query) use ($properties) {
        $query->whereIn('property_id', $properties);
      })->where('status', true)->sum('amount');

      $pendingRevenue = \App\Models\Payment::whereHas('flat', function ($query) use ($properties) {
        $query->whereIn('property_id', $properties);
      })->where('status', false)->sum('amount');

      return [
        'total_revenue' => $totalRevenue,
        'pending_revenue' => $pendingRevenue,
        'properties_count' => count($properties),
        'last_updated' => now()->toISOString(),
      ];
    }, 1800, $agencyId); // Cache pour 30 minutes
  }

  /**
   * Cache les contrats d'un tenant
   */
  public static function cacheTenantContracts(int $tenantId, ?int $agencyId = null): \Illuminate\Database\Eloquent\Collection
  {
    return self::remember("tenant_contracts:{$tenantId}", function () use ($tenantId) {
      return \App\Models\Contract::where('tenant_id', $tenantId)
        ->with(['property', 'flat', 'payments'])
        ->get();
    }, 3600, $agencyId); // Cache pour 1 heure
  }

  /**
   * Invalide le cache quand des données sont modifiées
   */
  public static function invalidateRelatedCache(string $model, int $modelId, ?int $agencyId = null): void
  {
    $agencyId = $agencyId ?? self::getCurrentAgencyId();

    switch ($model) {
      case 'Property':
        // Invalider le cache des stats de l'agence
        self::forget('agency_stats', $agencyId);

        // Invalider le cache du propriétaire
        $property = \App\Models\Property::find($modelId);
        if ($property && $property->owner_id) {
          self::forget("owner_properties:{$property->owner_id}", $agencyId);
          self::forget("owner_revenue:{$property->owner_id}", $agencyId);
        }
        break;

      case 'Contract':
        self::forget('agency_stats', $agencyId);

        $contract = \App\Models\Contract::find($modelId);
        if ($contract) {
          // Invalider le cache du tenant
          self::forget("tenant_contracts:{$contract->tenant_id}", $agencyId);

          // Invalider le cache du propriétaire
          if ($contract->property && $contract->property->owner_id) {
            self::forget("owner_properties:{$contract->property->owner_id}", $agencyId);
            self::forget("owner_revenue:{$contract->property->owner_id}", $agencyId);
          }
        }
        break;

      case 'Payment':
        self::forget('agency_stats', $agencyId);

        $payment = \App\Models\Payment::find($modelId);
        if ($payment) {
          // Invalider le cache du tenant
          self::forget("tenant_contracts:{$payment->tenant_id}", $agencyId);

          // Invalider le cache du propriétaire
          if ($payment->flat && $payment->flat->property && $payment->flat->property->owner_id) {
            self::forget("owner_revenue:{$payment->flat->property->owner_id}", $agencyId);
          }
        }
        break;
    }
  }

  /**
   * Préchauffe le cache avec les données les plus utilisées
   */
  public static function warmupCache(?int $agencyId = null): void
  {
    $agencyId = $agencyId ?? self::getCurrentAgencyId();

    if (!$agencyId) {
      return;
    }

    // Préchauffer les stats de l'agence
    self::cacheAgencyStats($agencyId);

    // Préchauffer les données des owners qui ont des propriétés dans cette agence
    $owners = \App\Models\Property::where('agency_id', $agencyId)
      ->whereNotNull('owner_id')
      ->distinct()
      ->pluck('owner_id');

    foreach ($owners as $ownerId) {
      self::cacheOwnerProperties($ownerId, $agencyId);
      self::cacheOwnerRevenue($ownerId, $agencyId);
    }
  }
}
