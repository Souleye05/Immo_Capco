<?php

return [
    App\Providers\AppServiceProvider::class,
    // App\Providers\Filament\LoginPanelProvider::class, // Temporairement désactivé pour éviter les conflits
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\Filament\SuperAdminPanelProvider::class,
    App\Providers\Filament\OwnerPanelProvider::class,
    App\Providers\Filament\TenantPanelProvider::class,
];
