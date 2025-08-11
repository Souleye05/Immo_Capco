<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Tenancy\EditTeamProfile;
use App\Filament\Pages\Tenancy\RegisterTeam;
use App\Models\Agency;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Navigation\NavigationItem;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Http\Middleware\PanelAccessMiddleware;

class OwnerPanelProvider extends PanelProvider
{
  public function panel(Panel $panel): Panel
  {
    return $panel
      ->id('owner')
      ->path('owner')
      // ->login() // Désactivé - utilise le panel de redirection unifié
      // ->registration() // Désactivé - utilise le panel de redirection unifié
      ->colors([
        'primary' => Color::Green,
      ])
      ->brandName('Immo - Propriétaire')
      ->brandLogo(asset('images/logo.png'))
      ->brandLogoHeight(100)
      ->font('Poppins')
      // Navigation personnalisée pour les locataires
      ->navigationItems([
        NavigationItem::make('Support')
          ->url('/tenant/support')
          ->icon('heroicon-o-chat-bubble-left-ellipsis')
          ->group('Aide')
          ->sort(100),
        NavigationItem::make('FAQ')
          ->url('/tenant/faq')
          ->icon('heroicon-o-question-mark-circle')
          ->group('Aide')
          ->sort(101),
      ])
      ->discoverResources(in: app_path('Filament/Owner/Resources'), for: 'App\\Filament\\Owner\\Resources')
      ->discoverPages(in: app_path('Filament/Owner/Pages'), for: 'App\\Filament\\Owner\\Pages')
      ->pages([
        Pages\Dashboard::class,
      ])
      ->discoverWidgets(in: app_path('Filament/Owner/Widgets'), for: 'App\\Filament\\Owner\\Widgets')
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
      ->tenant(Agency::class, ownershipRelationship: 'agencys', slugAttribute: 'slug')
      ->tenantRegistration(RegisterTeam::class)
      ->tenantProfile(EditTeamProfile::class)
      ->authMiddleware([
        Authenticate::class,
        PanelAccessMiddleware::class,
      ])
      // Personnalisation UI pour les locataires
      ->sidebarCollapsibleOnDesktop()
      ->sidebarWidth('15rem')
      ->maxContentWidth('full')
      ->topNavigation(false)
      ->globalSearchKeyBindings(['command+k', 'ctrl+k']);
    // ->databaseNotifications()
    // ->databaseNotificationsPolling('30s');
  }
}
