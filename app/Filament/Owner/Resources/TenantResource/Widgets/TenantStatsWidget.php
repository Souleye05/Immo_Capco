<?php

namespace App\Filament\Owner\Resources\TenantResource\Widgets;

use App\Enums\ContractStatus;
use App\Filament\Owner\Resources\TenantResource;
use App\Filament\Owner\Resources\TenantResource\Pages\ListTenants;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\Tenant;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class TenantStatsWidget extends BaseWidget
{
  use InteractsWithPageTable;

  protected function getTablePage(): string
  {
    return ListTenants::class;
  }

  protected function getStats(): array
  {
    $user = auth()->user();

    if (!$user || !$user->canAccessOwnerPanel()) {
      return [
        Stat::make('Total Locataires', '0'),
        Stat::make('Contrats Actifs', '0'),
        Stat::make('Paiements en Retard', '0'),
      ];
    }

    try {
      // Get base query for owner's tenants
      $tenantsQuery = TenantResource::getEloquentQuery();

      // Total tenants
      $totalTenants = $tenantsQuery->count();

      // Active contracts
      $activeContracts = $tenantsQuery->clone()
        ->whereHas('contracts', function (Builder $query) {
          $query->where('status', ContractStatus::ACTIVE);
        })
        ->count();

      // Overdue payments - tenants with unpaid payments past due date
      $overduePayments = $tenantsQuery->clone()
        ->whereHas('allPayments', function (Builder $query) {
          $query->where('status', false) // Unpaid
            ->where('date_payment', '<', now()); // Past due
        })
        ->count();

      // Calculate occupancy rate
      $occupancyRate = $totalTenants > 0 ? round(($activeContracts / $totalTenants) * 100, 1) : 0;

      return [
        Stat::make('Total Locataires', $totalTenants)
          ->description('Nombre total de locataires')
          ->descriptionIcon('heroicon-m-users')
          ->color('primary'),

        Stat::make('Contrats Actifs', $activeContracts)
          ->description($occupancyRate . '% de taux d\'occupation')
          ->descriptionIcon('heroicon-m-document-check')
          ->color('success'),

        Stat::make('Paiements en Retard', $overduePayments)
          ->description('Locataires avec retards')
          ->descriptionIcon('heroicon-m-exclamation-triangle')
          ->color($overduePayments > 0 ? 'danger' : 'success'),
      ];
    } catch (\Exception $e) {
      // Log error and return safe defaults
      \Log::error('Error calculating tenant statistics', [
        'user_id' => $user->id,
        'error' => $e->getMessage(),
      ]);

      return [
        Stat::make('Total Locataires', '0')
          ->description('Erreur de calcul')
          ->color('gray'),
        Stat::make('Contrats Actifs', '0')
          ->description('Erreur de calcul')
          ->color('gray'),
        Stat::make('Paiements en Retard', '0')
          ->description('Erreur de calcul')
          ->color('gray'),
      ];
    }
  }
}
