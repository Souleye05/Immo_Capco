<?php

namespace App\Filament\Owner\Widgets;

use App\Models\Owner;
use App\Services\TenantCacheManager;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class CachedOwnerStats extends BaseWidget
{
  protected static ?string $pollingInterval = '60s';

  protected function getStats(): array
  {
    // Trouver l'owner correspondant à l'utilisateur connecté
    $owner = Owner::where('name', Auth::user()->name)->first();

    if (!$owner) {
      return [
        Stat::make('Erreur', 'Propriétaire non trouvé')
          ->description('Contactez l\'administrateur')
          ->descriptionIcon('heroicon-m-exclamation-triangle')
          ->color('danger'),
      ];
    }

    // Utiliser le cache tenant-aware pour les données de l'owner
    $revenueData = TenantCacheManager::cacheOwnerRevenue($owner->id);
    $properties = TenantCacheManager::cacheOwnerProperties($owner->id);

    // Calculer les statistiques à partir des données cachées
    $activeContracts = $properties->flatMap->contracts->where('status', 'active')->count();
    $totalContracts = $properties->flatMap->contracts->count();
    $totalFlats = $properties->sum('number_flat');

    return [
      Stat::make('Mes Propriétés', $revenueData['properties_count'])
        ->description('Propriétés possédées')
        ->descriptionIcon('heroicon-m-building-office')
        ->color('success'),

      Stat::make('Appartements', $totalFlats)
        ->description('Total des appartements')
        ->descriptionIcon('heroicon-m-home')
        ->color('info'),

      Stat::make('Contrats Actifs', $activeContracts)
        ->description('Sur ' . $totalContracts . ' contrats total')
        ->descriptionIcon('heroicon-m-document-text')
        ->color('primary'),

      Stat::make('Revenus Encaissés', number_format($revenueData['total_revenue'], 0, ',', ' ') . ' FCFA')
        ->description('Total des paiements reçus')
        ->descriptionIcon('heroicon-m-banknotes')
        ->color('success'),

      Stat::make('Revenus en Attente', number_format($revenueData['pending_revenue'], 0, ',', ' ') . ' FCFA')
        ->description('Paiements en attente')
        ->descriptionIcon('heroicon-m-clock')
        ->color('warning'),
    ];
  }

  protected function getColumns(): int
  {
    return 3;
  }
}
