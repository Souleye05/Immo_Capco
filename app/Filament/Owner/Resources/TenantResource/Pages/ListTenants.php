<?php

namespace App\Filament\Owner\Resources\TenantResource\Pages;

use App\Enums\ContractStatus;
use App\Filament\Owner\Resources\TenantResource;
// use App\Filament\Owner\Resources\TenantResource\Widgets\TenantStatsWidget;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Tenant;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;

class ListTenants extends ListRecords
{
  use ExposesTableToWidgets;

  protected static string $resource = TenantResource::class;

  protected function getHeaderActions(): array
  {
    return [
      // No create action since owners cannot create tenants
    ];
  }

  protected function getHeaderWidgets(): array
  {
    return [
      // Temporarily disabled to resolve component issue
      // TenantStatsWidget::class,
    ];
  }

  /**
   * Authorize access to the tenant list
   */
  protected function authorizeAccess(): void
  {
    parent::authorizeAccess();

    $user = auth()->user();

    // Enhanced authorization check
    if (!$user) {
      throw new AuthorizationException('User must be authenticated to view tenant list');
    }

    // Check if user can access owner panel
    if (!$user->canAccessOwnerPanel()) {
      throw new AuthorizationException('User is not authorized to access owner panel');
    }

    // Log access for audit trail
    \Log::info('Owner tenant list access', [
      'user_id' => $user->id,
      'user_email' => $user->email,
      'ip' => request()->ip(),
      'user_agent' => request()->userAgent(),
      'timestamp' => now()->toISOString(),
    ]);
  }

  /**
   * Handle mount with security validation
   */
  public function mount(): void
  {
    try {
      parent::mount();

      // Additional security validation after mounting
      $this->authorizeAccess();
    } catch (AuthorizationException $e) {
      // Redirect to dashboard with error message
      $this->redirect('/owner', navigate: true);

      // Show error notification
      \Filament\Notifications\Notification::make()
        ->title('Accès refusé')
        ->body('Vous n\'êtes pas autorisé à consulter la liste des locataires.')
        ->danger()
        ->send();

      return;
    } catch (\Exception $e) {
      // Log unexpected errors
      \Log::error('Error mounting ListTenants page', [
        'user_id' => auth()->id(),
        'error' => $e->getMessage(),
      ]);

      $this->redirect('/owner', navigate: true);

      \Filament\Notifications\Notification::make()
        ->title('Erreur')
        ->body('Une erreur est survenue lors du chargement de la liste des locataires.')
        ->danger()
        ->send();

      return;
    }
  }

  /**
   * Get the page title with context
   */
  public function getTitle(): string
  {
    $user = auth()->user();
    $count = 0;

    try {
      $count = static::getResource()::getEloquentQuery()->count();
    } catch (\Exception $e) {
      // Handle error gracefully
      \Log::warning('Error getting tenant count for title', [
        'user_id' => $user?->id,
        'error' => $e->getMessage(),
      ]);
    }

    return $count > 0 ? "Mes Locataires ({$count})" : "Mes Locataires";
  }

  /**
   * Get table query with additional security validation
   */
  protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
  {
    $user = auth()->user();

    if (!$user || !$user->canAccessOwnerPanel()) {
      // Return empty query if unauthorized
      return static::getResource()::getEloquentQuery()->whereRaw('1 = 0');
    }

    try {
      return static::getResource()::getEloquentQuery();
    } catch (\Exception $e) {
      // Log error and return empty query
      \Log::error('Error building tenant table query', [
        'user_id' => $user->id,
        'error' => $e->getMessage(),
      ]);

      return static::getResource()::getEloquentQuery()->whereRaw('1 = 0');
    }
  }
}
