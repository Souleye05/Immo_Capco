<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Tenancy\EditTeamProfile;
use App\Filament\Admin\Resources\ContractResource\Widgets\ContractStatsWidget;
use App\Filament\Widgets\ContractChartWidget;
use App\Filament\Widgets\EtatDesFacturesChart;
use App\Filament\Widgets\KpiStats;
use App\Filament\Widgets\RevenusChart;
use App\Filament\Widgets\RevenusVsDepensesChart;
use App\Filament\Widgets\TypesDeBiensChart;
use App\Models\Agency;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use App\Filament\Pages\Tenancy\RegisterTeam;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Http\Middleware\PanelAccessMiddleware;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default(false)
            ->id('admin')
            ->path('admin')
            // ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->brandName('Immo')
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight(100)

            ->font('Poppins')
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            // ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
                ContractStatsWidget::class,
                KpiStats::class,
                TypesDeBiensChart::class,
                EtatDesFacturesChart::class,
                RevenusChart::class,
                RevenusVsDepensesChart::class,
                ContractChartWidget::class,

            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                // FilamentShieldPlugin::make(),
            ])
            ->tenant(Agency::class, ownershipRelationship: 'agencys', slugAttribute: 'slug')
            ->tenantRegistration(RegisterTeam::class)
            ->tenantProfile(EditTeamProfile::class)
            ->authMiddleware([
                Authenticate::class,
                PanelAccessMiddleware::class,
            ]);
    }
}
