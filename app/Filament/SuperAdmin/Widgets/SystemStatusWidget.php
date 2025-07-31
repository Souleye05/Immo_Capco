<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\SystemSetting;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemStatusWidget extends BaseWidget
{
  protected static ?int $sort = 1;

  protected function getStats(): array
  {
    $maintenanceMode = SystemSetting::get('maintenance_mode', false);
    $cacheEnabled = SystemSetting::get('cache_enabled', true);
    $emailNotifications = SystemSetting::get('email_notifications_enabled', true);
    $twoFactorAuth = SystemSetting::get('enable_two_factor_auth', false);

    return [
      Stat::make('System Status', $maintenanceMode ? 'Maintenance' : 'Operational')
        ->description($maintenanceMode ? 'System is in maintenance mode' : 'All systems operational')
        ->descriptionIcon($maintenanceMode ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
        ->color($maintenanceMode ? 'warning' : 'success'),

      Stat::make('Cache Status', $cacheEnabled ? 'Enabled' : 'Disabled')
        ->description('Application caching')
        ->descriptionIcon($cacheEnabled ? 'heroicon-m-bolt' : 'heroicon-m-bolt-slash')
        ->color($cacheEnabled ? 'success' : 'warning'),

      Stat::make('Email Notifications', $emailNotifications ? 'Active' : 'Disabled')
        ->description('System email notifications')
        ->descriptionIcon($emailNotifications ? 'heroicon-m-envelope' : 'heroicon-m-envelope-open')
        ->color($emailNotifications ? 'success' : 'danger'),

      Stat::make('Two-Factor Auth', $twoFactorAuth ? 'Required' : 'Optional')
        ->description('Security requirement')
        ->descriptionIcon($twoFactorAuth ? 'heroicon-m-shield-check' : 'heroicon-m-shield-exclamation')
        ->color($twoFactorAuth ? 'success' : 'warning'),
    ];
  }
}
