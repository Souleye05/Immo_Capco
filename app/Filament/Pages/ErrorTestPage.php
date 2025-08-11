<?php

namespace App\Filament\Pages;

use App\Exceptions\InvalidTenantException;
use App\Exceptions\NoAgencyAccessException;
use App\Exceptions\NoValidRoleException;
use App\Exceptions\PanelRedirectionException;
use App\Exceptions\TenantSelectionRequiredException;
use App\Services\RedirectionErrorHandler;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ErrorTestPage extends Page
{
  protected static ?string $navigationIcon = 'heroicon-o-bug-ant';

  protected static string $view = 'filament.pages.error-test';

  protected static bool $shouldRegisterNavigation = false;

  public static function getNavigationLabel(): string
  {
    return 'Test des Erreurs';
  }

  public function getTitle(): string
  {
    return 'Test du Système de Gestion d\'Erreurs';
  }

  public function getHeading(): string
  {
    return '🧪 Test du Système de Gestion d\'Erreurs';
  }

  public function getSubheading(): ?string
  {
    return 'Testez les différents types d\'erreurs et leurs notifications Filament.';
  }

  protected function getHeaderActions(): array
  {
    return [
      Action::make('testNoValidRole')
        ->label('Test: Aucun Rôle')
        ->icon('heroicon-o-user-minus')
        ->color('danger')
        ->action(function () {
          $errorHandler = app(RedirectionErrorHandler::class);
          $exception = new NoValidRoleException('Test: Utilisateur sans rôle valide');
          $errorHandler->handleNoValidRole($exception, auth()->id());
        }),

      Action::make('testNoAgencyAccess')
        ->label('Test: Aucune Agence')
        ->icon('heroicon-o-building-office-2')
        ->color('warning')
        ->action(function () {
          $errorHandler = app(RedirectionErrorHandler::class);
          $exception = new NoAgencyAccessException('Test: Utilisateur sans accès agence');
          $errorHandler->handleNoAgencyAccess($exception, auth()->id());
        }),

      Action::make('testInvalidTenant')
        ->label('Test: Agence Invalide')
        ->icon('heroicon-o-exclamation-triangle')
        ->color('warning')
        ->action(function () {
          $errorHandler = app(RedirectionErrorHandler::class);
          $exception = new InvalidTenantException('Test: Agence invalide sélectionnée');
          $errorHandler->handleInvalidTenant($exception, auth()->id());
        }),

      Action::make('testRedirectionFailed')
        ->label('Test: Redirection Échouée')
        ->icon('heroicon-o-exclamation-circle')
        ->color('danger')
        ->action(function () {
          $errorHandler = app(RedirectionErrorHandler::class);
          $exception = new PanelRedirectionException('Test: Échec de redirection');
          $errorHandler->handlePanelRedirection($exception, auth()->id());
        }),

      Action::make('testTenantSelection')
        ->label('Test: Sélection Requise')
        ->icon('heroicon-o-building-office')
        ->color('info')
        ->action(function () {
          $errorHandler = app(RedirectionErrorHandler::class);
          $exception = new TenantSelectionRequiredException('Test: Sélection agence requise');
          $errorHandler->handleTenantSelectionRequired($exception, auth()->id());
        }),

      Action::make('testGenericError')
        ->label('Test: Erreur Générique')
        ->icon('heroicon-o-exclamation-circle')
        ->color('gray')
        ->action(function () {
          $errorHandler = app(RedirectionErrorHandler::class);
          $exception = new \Exception('Test: Erreur générique');
          $errorHandler->handleGenericError($exception, auth()->id());
        }),
    ];
  }

  public function getViewData(): array
  {
    $errorHandler = app(RedirectionErrorHandler::class);

    return [
      'errorStats' => $errorHandler->getErrorStats(),
      'user' => auth()->user(),
      'testResults' => session('test_results', []),
    ];
  }
}
