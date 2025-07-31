<?php

namespace App\Filament\Widgets;

use App\Services\TenantCacheManager;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CachedKpiStats extends BaseWidget
{
  protected static ?string $pollingInterval = '30s';

  protected function getStats(): array
  {
    // Utiliser le cache tenant-aware pour les statistiques
    $stats = TenantCacheManager::cacheAgencyStats();

    return [
      Stat::make('Propriétés', $stats['properties_count'])
        ->description('Total des propriétés')
        ->descriptionIcon('heroicon-m-building-office')
        ->color('success'),

      Stat::make('Contrats Actifs', $stats['active_contracts_count'])
        ->description('Sur ' . $stats['contracts_count'] . ' contrats total')
        ->descriptionIcon('heroicon-m-document-text')
        ->color('info'),

      Stat::make('Revenus Total', number_format($stats['total_revenue'], 0, ',', ' ') . ' FCFA')
        ->description('Paiements encaissés')
        ->descriptionIcon('heroicon-m-banknotes')
        ->color('success'),

      Stat::make('En Attente', number_format($stats['pending_payments'], 0, ',', ' ') . ' FCFA')
        ->description('Paiements en attente')
        ->descriptionIcon('heroicon-m-clock')
        ->color('warning'),

      Stat::make('Locataires', $stats['tenants_count'])
        ->description('Total des locataires')
        ->descriptionIcon('heroicon-m-users')
        ->color('info'),

      Stat::make('Factures', $stats['payments_count'])
        ->description('Total des factures')
        ->descriptionIcon('heroicon-m-document-duplicate')
        ->color('primary'),
    ];
  }

  protected function getColumns(): int
  {
    return 3;
  }
}
