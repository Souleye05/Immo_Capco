<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Agency;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsWidget extends BaseWidget
{
  protected function getStats(): array
  {
    return [
      Stat::make('Total Agencies', Agency::count())
        ->description('Active agencies on platform')
        ->descriptionIcon('heroicon-m-building-office')
        ->color('success'),

      Stat::make('Total Users', User::count())
        ->description('All users across agencies')
        ->descriptionIcon('heroicon-m-users')
        ->color('info'),

      Stat::make('Users per Agency', number_format(User::count() / max(Agency::count(), 1), 1))
        ->description('Average users per agency')
        ->descriptionIcon('heroicon-m-chart-bar')
        ->color('warning'),
    ];
  }
}
