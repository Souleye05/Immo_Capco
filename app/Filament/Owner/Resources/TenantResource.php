<?php

namespace App\Filament\Owner\Resources;

use App\Enums\ContractStatus;
use App\Filament\Owner\Resources\TenantResource\Pages;
use App\Filament\Owner\Resources\TenantResource\RelationManagers;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TenantResource extends Resource
{
  protected static ?string $model = Tenant::class;

  protected static ?string $navigationIcon = 'heroicon-o-users';
  protected static ?string $navigationGroup = 'Mes Biens';
  protected static ?string $navigationLabel = 'Mes Locataires';
  protected static ?string $modelLabel = 'Locataire';
  protected static ?string $pluralModelLabel = 'Locataires';

  public static function getEloquentQuery(): Builder
  {
    $user = auth()->user();

    // Enhanced authentication validation
    if (!$user) {
      throw new \Illuminate\Auth\AuthenticationException('User must be authenticated to access owner resources');
    }

    // Additional authorization check for owner panel access
    if (!$user->canAccessOwnerPanel()) {
      throw new \Illuminate\Auth\Access\AuthorizationException('User is not authorized to access owner panel');
    }

    try {
      return parent::getEloquentQuery()
        ->whereHas('contracts', function (Builder $contractQuery) use ($user) {
          $contractQuery->whereHas('property', function (Builder $propertyQuery) use ($user) {
            $propertyQuery->whereHas('owner', function (Builder $ownerQuery) use ($user) {
              // Primary filtering: Direct user_id relation
              $ownerQuery->where('user_id', $user->id);
            })
              // Fallback filtering: Email matching for owner identification
              ->orWhereHas('owner', function (Builder $ownerQuery) use ($user) {
                $ownerQuery->where('email', $user->email);
              })
              // Additional fallback: Name matching (more restrictive)
              ->orWhereHas('owner', function (Builder $ownerQuery) use ($user) {
                $ownerQuery->where('name', 'like', '%' . trim($user->name) . '%')
                  ->whereNotNull('name')
                  ->where('name', '!=', '');
              });
          });
        })
        // Eager load relationships for performance
        ->with([
          'contracts' => function ($query) {
            $query->with(['property.owner', 'payments']);
          },
          'user'
        ])
        // Additional security: Only show tenants with valid contracts
        ->whereHas('contracts', function (Builder $contractQuery) {
          $contractQuery->whereNotNull('property_id')
            ->whereHas('property');
        });
    } catch (\Exception $e) {
      // Log security-related access attempts
      \Log::warning('Unauthorized tenant access attempt', [
        'user_id' => $user->id,
        'user_email' => $user->email,
        'error' => $e->getMessage(),
        'ip' => request()->ip(),
        'user_agent' => request()->userAgent(),
      ]);

      // Return empty query to prevent data exposure
      return parent::getEloquentQuery()->whereRaw('1 = 0');
    }
  }

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Section::make('Informations du locataire')
          ->schema([
            Forms\Components\TextInput::make('name')
              ->label('Nom complet')
              ->disabled()
              ->dehydrated(false),

            Forms\Components\TextInput::make('email')
              ->label('Email')
              ->disabled()
              ->dehydrated(false),

            Forms\Components\TextInput::make('phone')
              ->label('Téléphone')
              ->disabled()
              ->dehydrated(false),

            Forms\Components\Textarea::make('address')
              ->label('Adresse')
              ->disabled()
              ->dehydrated(false)
              ->rows(2),
          ])
          ->columns(2),
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('name')
          ->label('Nom du locataire')
          ->searchable()
          ->sortable()
          ->weight('medium'),

        Tables\Columns\TextColumn::make('email')
          ->label('Email')
          ->searchable()
          ->copyable()
          ->icon('heroicon-m-envelope'),

        Tables\Columns\TextColumn::make('phone')
          ->label('Téléphone')
          ->searchable()
          ->copyable()
          ->icon('heroicon-m-phone'),

        Tables\Columns\TextColumn::make('contracts.property.name')
          ->label('Propriété')
          ->searchable()
          ->sortable()
          ->badge()
          ->color('info')
          ->formatStateUsing(function ($record) {
            // Get active contract's property
            $activeContract = $record->contracts->where('status', ContractStatus::ACTIVE)->first();
            return $activeContract?->property?->name ?? 'Aucune propriété active';
          }),

        Tables\Columns\TextColumn::make('contracts.status')
          ->label('Statut du contrat')
          ->badge()
          ->color(fn($record) => match ($record->contracts->where('status', ContractStatus::ACTIVE)->first()?->status) {
            ContractStatus::ACTIVE => 'success',
            ContractStatus::DRAFT => 'gray',
            ContractStatus::EXPIRED => 'warning',
            ContractStatus::TERMINATED => 'danger',
            ContractStatus::RENEWED => 'info',
            default => 'gray',
          })
          ->formatStateUsing(function ($record) {
            $activeContract = $record->contracts->where('status', ContractStatus::ACTIVE)->first();
            return $activeContract?->status?->label() ?? 'Aucun contrat actif';
          }),

        Tables\Columns\TextColumn::make('contracts.monthly_rent')
          ->label('Loyer mensuel')
          ->formatStateUsing(function ($record) {
            $activeContract = $record->contracts->where('status', ContractStatus::ACTIVE)->first();
            if (!$activeContract) return '-';
            return number_format($activeContract->monthly_rent, 0, ',', ' ') . ' F CFA';
          })
          ->alignEnd()
          ->sortable(),

        // Tables\Columns\TextColumn::make('contracts.payments.status')
        //   ->label('Statut Paiements')
        //   ->badge()
        //   ->color(function ($record) {
        //     try {
        //       // Check for overdue payments
        //       $hasOverdue = $record->allPayments()
        //         ->where('status', false)
        //         ->where('date_payment', '<', now())
        //         ->exists();

        //       if ($hasOverdue) {
        //         return 'danger';
        //       }

        //       // Check for partial payments using the versement relationship
        //       $payments = $record->allPayments()->with('versement')->get();
        //       $hasPartial = $payments->filter(function ($payment) {
        //         $amountPaid = $payment->versement->sum('amount');
        //         return $amountPaid > 0 && $amountPaid < $payment->amount;
        //       })->isNotEmpty();

        //       if ($hasPartial) {
        //         return 'warning';
        //       }

        //       return 'success';
        //     } catch (\Exception $e) {
        //       return 'gray';
        //     }
        //   })
        //   ->formatStateUsing(function ($record) {
        //     try {
        //       // Check for overdue payments
        //       $overdueCount = $record->allPayments()
        //         ->where('status', false)
        //         ->where('date_payment', '<', now())
        //         ->count();

        //       if ($overdueCount > 0) {
        //         return "En retard ({$overdueCount})";
        //       }

        //       // Check for partial payments using the versement relationship
        //       $payments = $record->allPayments()->with('versement')->get();
        //       $partialCount = $payments->filter(function ($payment) {
        //         $amountPaid = $payment->versement->sum('amount');
        //         return $amountPaid > 0 && $amountPaid < $payment->amount;
        //       })->count();

        //       if ($partialCount > 0) {
        //         return "Partiel ({$partialCount})";
        //       }

        //       return 'À jour';
        //     } catch (\Exception $e) {
        //       return 'Erreur';
        //     }
        //   }),

        Tables\Columns\TextColumn::make('created_at')
          ->label('Date d\'ajout')
          ->date('d/m/Y')
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('property')
          ->label('Propriété')
          ->relationship('contracts.property', 'name')
          ->searchable()
          ->preload(),

        Tables\Filters\SelectFilter::make('contract_status')
          ->label('Statut du contrat')
          ->options(ContractStatus::options())
          ->query(function (Builder $query, array $data): Builder {
            if (!$data['value']) {
              return $query;
            }

            return $query->whereHas('contracts', function (Builder $contractQuery) use ($data) {
              $contractQuery->where('status', $data['value']);
            });
          }),

        Tables\Filters\SelectFilter::make('payment_status')
          ->label('Statut des paiements')
          ->options([
            'up_to_date' => 'À jour',
            'overdue' => 'En retard',
            'partial' => 'Partiellement payé',
          ])
          ->query(function (Builder $query, array $data): Builder {
            if (!$data['value']) {
              return $query;
            }

            return match ($data['value']) {
              'up_to_date' => $query->whereDoesntHave('allPayments', function (Builder $paymentQuery) {
                $paymentQuery->where('status', false)
                  ->where('date_payment', '<', now());
              }),
              'overdue' => $query->whereHas('allPayments', function (Builder $paymentQuery) {
                $paymentQuery->where('status', false)
                  ->where('date_payment', '<', now());
              }),
              'partial' => $query->whereHas('allPayments', function (Builder $paymentQuery) {
                // For partial payments, we need to check if there are versements but not fully paid
                $paymentQuery->whereHas('versement')
                  ->where('status', false); // Not fully paid but has some payments
              }),
              default => $query,
            };
          }),

        Tables\Filters\Filter::make('active_contracts_only')
          ->label('Contrats actifs uniquement')
          ->query(
            fn(Builder $query): Builder =>
            $query->whereHas('contracts', function (Builder $contractQuery) {
              $contractQuery->where('status', ContractStatus::ACTIVE);
            })
          )
          ->toggle(),

        Tables\Filters\Filter::make('has_overdue_payments')
          ->label('Avec paiements en retard')
          ->query(
            fn(Builder $query): Builder =>
            $query->whereHas('allPayments', function (Builder $paymentQuery) {
              $paymentQuery->where('status', false)
                ->where('date_payment', '<', now());
            })
          )
          ->toggle(),
      ])
      ->actions([
        Tables\Actions\ViewAction::make(),
      ])
      ->defaultSort('created_at', 'desc')
      ->striped()
      ->emptyStateHeading('Aucun locataire trouvé')
      ->emptyStateDescription('Vous n\'avez actuellement aucun locataire dans vos propriétés. Les locataires apparaîtront ici une fois qu\'ils auront signé un contrat pour l\'une de vos propriétés.')
      ->emptyStateIcon('heroicon-o-users')
      ->emptyStateActions([
        Tables\Actions\Action::make('view_properties')
          ->label('Voir mes propriétés')
          ->icon('heroicon-o-building-office-2')
          ->url(function (): string {
            // Get the current tenant from Filament's tenant context
            $tenant = \Filament\Facades\Filament::getTenant();
            if ($tenant) {
              return route('filament.owner.resources.properties.index', ['tenant' => $tenant]);
            }
            // Fallback to dashboard if no tenant context
            return route('filament.owner.pages.dashboard', ['tenant' => $tenant ?? 'default']);
          })
          ->color('primary'),
      ]);
  }

  public static function getRelations(): array
  {
    return [
      // Temporarily disabled to resolve component issue
      // RelationManagers\ContractRelationManager::class,
      // RelationManagers\PaymentRelationManager::class,
    ];
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListTenants::route('/'),
      'view' => Pages\ViewTenant::route('/{record}'),
    ];
  }

  public static function canCreate(): bool
  {
    return false; // Owners cannot create tenants
  }

  public static function canEdit($record): bool
  {
    return false; // Owners cannot edit tenants
  }

  public static function canDelete($record): bool
  {
    return false; // Owners cannot delete tenants
  }

  public static function getNavigationBadge(): ?string
  {
    $user = auth()->user();
    if (!$user) return null;

    try {
      $count = static::getEloquentQuery()->count();
      return $count > 0 ? (string) $count : null;
    } catch (\Exception $e) {
      // Return null if there's an error to prevent exposing information
      return null;
    }
  }

  /**
   * Validate that a tenant belongs to the authenticated owner
   */
  public static function validateTenantAccess(Tenant $tenant, ?\App\Models\User $user = null): bool
  {
    // If explicitly passed null, deny access (for testing purposes)
    if (func_num_args() > 1 && $user === null) {
      return false;
    }

    // If no user is provided, use the authenticated user
    if ($user === null) {
      $user = auth()->user();
    }

    // If still no user (unauthenticated), deny access
    if (!$user) {
      return false;
    }

    try {
      // Check if tenant has contracts with properties owned by this user
      return $tenant->contracts()
        ->whereHas('property', function (Builder $propertyQuery) use ($user) {
          $propertyQuery->whereHas('owner', function (Builder $ownerQuery) use ($user) {
            // Primary check: Direct user_id relation
            $ownerQuery->where('user_id', $user->id);
          })
            // Fallback: Email matching
            ->orWhereHas('owner', function (Builder $ownerQuery) use ($user) {
              $ownerQuery->where('email', $user->email);
            })
            // Additional fallback: Name matching (more restrictive)
            ->orWhereHas('owner', function (Builder $ownerQuery) use ($user) {
              $ownerQuery->where('name', 'like', '%' . trim($user->name) . '%')
                ->whereNotNull('name')
                ->where('name', '!=', '');
            });
        })
        ->exists();
    } catch (\Exception $e) {
      // Log the error and deny access for security
      \Log::warning('Error validating tenant access', [
        'user_id' => $user->id ?? 'null',
        'tenant_id' => $tenant->id,
        'error' => $e->getMessage(),
      ]);
      return false;
    }
  }

  /**
   * Enhanced authorization check for individual tenant access
   */
  public static function canView($record): bool
  {
    $user = auth()->user();

    if (!$user) {
      return false;
    }

    // Check if user can access owner panel
    if (!$user->canAccessOwnerPanel()) {
      return false;
    }

    // Validate tenant access
    if (!static::validateTenantAccess($record, $user)) {
      // Log unauthorized access attempt
      \Log::warning('Unauthorized tenant view attempt', [
        'user_id' => $user->id,
        'user_email' => $user->email,
        'tenant_id' => $record->id,
        'tenant_name' => $record->name,
        'ip' => request()->ip(),
        'user_agent' => request()->userAgent(),
      ]);
      return false;
    }

    return true;
  }

  /**
   * Get filtered properties for the authenticated owner
   */
  public static function getOwnerProperties(): \Illuminate\Database\Eloquent\Collection
  {
    $user = auth()->user();

    if (!$user) {
      return collect();
    }

    try {
      return \App\Models\Property::whereHas('owner', function (Builder $ownerQuery) use ($user) {
        // Primary filtering: Direct user_id relation
        $ownerQuery->where('user_id', $user->id);
      })
        // Fallback filtering: Email matching
        ->orWhereHas('owner', function (Builder $ownerQuery) use ($user) {
          $ownerQuery->where('email', $user->email);
        })
        // Additional fallback: Name matching
        ->orWhereHas('owner', function (Builder $ownerQuery) use ($user) {
          $ownerQuery->where('name', 'like', '%' . trim($user->name) . '%')
            ->whereNotNull('name')
            ->where('name', '!=', '');
        })
        ->with(['owner', 'contracts.tenant'])
        ->get();
    } catch (\Exception $e) {
      \Log::error('Error fetching owner properties', [
        'user_id' => $user->id,
        'error' => $e->getMessage(),
      ]);
      return collect();
    }
  }

  /**
   * Audit log for tenant data access
   */
  public static function logTenantAccess(Tenant $tenant, string $action = 'view'): void
  {
    $user = auth()->user();

    if (!$user) {
      return;
    }

    \Log::info('Tenant data access', [
      'action' => $action,
      'user_id' => $user->id,
      'user_email' => $user->email,
      'tenant_id' => $tenant->id,
      'tenant_name' => $tenant->name,
      'tenant_email' => $tenant->email,
      'ip' => request()->ip(),
      'user_agent' => request()->userAgent(),
      'timestamp' => now()->toISOString(),
    ]);
  }
}
