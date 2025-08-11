<?php

namespace App\Filament\Owner\Resources\TenantResource\RelationManagers;

use App\Enums\ContractStatus;
use App\Filament\Owner\Resources\TenantResource;
use App\Models\Contract;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ContractRelationManager extends RelationManager
{
  protected static string $relationship = 'contracts';

  protected static ?string $title = 'Contrats';
  protected static ?string $modelLabel = 'Contrat';
  protected static ?string $pluralModelLabel = 'Contrats';
  protected static ?string $icon = 'heroicon-o-document-text';

  /**
   * Filter contracts to only show those belonging to the owner's properties
   */
  public function getEloquentQuery(): Builder
  {
    $user = auth()->user();

    if (!$user) {
      return parent::getEloquentQuery()->whereRaw('1 = 0');
    }

    return parent::getEloquentQuery()
      ->whereHas('property', function (Builder $propertyQuery) use ($user) {
        $propertyQuery->whereHas('owner', function (Builder $ownerQuery) use ($user) {
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
          });
      })
      ->with(['property', 'payments'])
      ->orderBy('created_at', 'desc');
  }

  public function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Section::make('Informations du contrat')
          ->schema([
            Forms\Components\Grid::make(2)
              ->schema([
                Forms\Components\TextInput::make('contract_number')
                  ->label('Numéro de contrat')
                  ->disabled()
                  ->dehydrated(false),

                Forms\Components\Select::make('status')
                  ->label('Statut')
                  ->options(ContractStatus::options())
                  ->disabled()
                  ->dehydrated(false),

                Forms\Components\TextInput::make('property.name')
                  ->label('Propriété')
                  ->disabled()
                  ->dehydrated(false),

                Forms\Components\TextInput::make('monthly_rent')
                  ->label('Loyer mensuel')
                  ->disabled()
                  ->dehydrated(false)
                  ->formatStateUsing(function ($state) {
                    return $state ? number_format($state, 0, ',', ' ') . ' F CFA' : '-';
                  }),

                Forms\Components\DatePicker::make('start_date')
                  ->label('Date de début')
                  ->disabled()
                  ->dehydrated(false),

                Forms\Components\DatePicker::make('end_date')
                  ->label('Date de fin')
                  ->disabled()
                  ->dehydrated(false),

                Forms\Components\TextInput::make('contract_duration_months')
                  ->label('Durée (mois)')
                  ->disabled()
                  ->dehydrated(false)
                  ->formatStateUsing(function ($state) {
                    return $state ? $state . ' mois' : '-';
                  }),

                Forms\Components\TextInput::make('cautions')
                  ->label('Caution')
                  ->disabled()
                  ->dehydrated(false)
                  ->formatStateUsing(function ($state) {
                    return $state ? number_format($state, 0, ',', ' ') . ' F CFA' : '-';
                  }),
              ]),

            Forms\Components\Textarea::make('notes')
              ->label('Notes')
              ->disabled()
              ->dehydrated(false)
              ->rows(3)
              ->columnSpanFull(),
          ]),
      ]);
  }

  public function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('contract_number')
      ->columns([
        Tables\Columns\TextColumn::make('contract_number')
          ->label('N° Contrat')
          ->searchable()
          ->sortable()
          ->weight('medium')
          ->copyable(),

        Tables\Columns\TextColumn::make('property.name')
          ->label('Propriété')
          ->searchable()
          ->sortable()
          ->badge()
          ->color('info'),

        Tables\Columns\TextColumn::make('status')
          ->label('Statut')
          ->badge()
          ->color(fn(ContractStatus $state): string => $state->color())
          ->icon(fn(ContractStatus $state): string => $state->icon())
          ->formatStateUsing(fn(ContractStatus $state): string => $state->label()),

        Tables\Columns\TextColumn::make('start_date')
          ->label('Début')
          ->date('d/m/Y')
          ->sortable(),

        Tables\Columns\TextColumn::make('end_date')
          ->label('Fin')
          ->date('d/m/Y')
          ->sortable()
          ->color(function (Model $record): string {
            if (!$record->end_date) return 'gray';

            $daysUntilExpiration = now()->diffInDays($record->end_date, false);

            if ($daysUntilExpiration < 0) {
              return 'danger'; // Expired
            } elseif ($daysUntilExpiration <= 30) {
              return 'warning'; // Expiring soon
            } elseif ($daysUntilExpiration <= 90) {
              return 'info'; // Expiring in 3 months
            }

            return 'success'; // Still valid
          })
          ->icon(function (Model $record): ?string {
            if (!$record->end_date) return null;

            $daysUntilExpiration = now()->diffInDays($record->end_date, false);

            if ($daysUntilExpiration < 0) {
              return 'heroicon-o-exclamation-triangle';
            } elseif ($daysUntilExpiration <= 30) {
              return 'heroicon-o-clock';
            }

            return null;
          }),

        Tables\Columns\TextColumn::make('monthly_rent')
          ->label('Loyer')
          ->formatStateUsing(function ($state) {
            return $state ? number_format($state, 0, ',', ' ') . ' F' : '-';
          })
          ->alignEnd()
          ->sortable(),

        Tables\Columns\TextColumn::make('expiration_status')
          ->label('Expiration')
          ->badge()
          ->color(function (Model $record): string {
            if (!$record->end_date) return 'gray';

            $daysUntilExpiration = now()->diffInDays($record->end_date, false);

            if ($daysUntilExpiration < 0) {
              return 'danger';
            } elseif ($daysUntilExpiration <= 30) {
              return 'warning';
            } elseif ($daysUntilExpiration <= 90) {
              return 'info';
            }

            return 'success';
          })
          ->formatStateUsing(function (Model $record): string {
            if (!$record->end_date) return 'Non définie';

            $daysUntilExpiration = now()->diffInDays($record->end_date, false);

            if ($daysUntilExpiration < 0) {
              return 'Expiré (' . abs($daysUntilExpiration) . 'j)';
            } elseif ($daysUntilExpiration === 0) {
              return 'Expire aujourd\'hui';
            } elseif ($daysUntilExpiration <= 30) {
              return 'Expire dans ' . $daysUntilExpiration . 'j';
            } elseif ($daysUntilExpiration <= 90) {
              return 'Expire dans ' . $daysUntilExpiration . 'j';
            }

            return 'Valide';
          }),

        Tables\Columns\TextColumn::make('created_at')
          ->label('Créé le')
          ->date('d/m/Y')
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('status')
          ->label('Statut du contrat')
          ->options(ContractStatus::options())
          ->multiple(),

        Tables\Filters\SelectFilter::make('property')
          ->label('Propriété')
          ->relationship('property', 'name')
          ->searchable()
          ->preload(),

        Tables\Filters\Filter::make('expiring_soon')
          ->label('Expire bientôt (30 jours)')
          ->query(
            fn(Builder $query): Builder =>
            $query->where('end_date', '<=', now()->addDays(30))
              ->where('end_date', '>', now())
          )
          ->toggle(),

        Tables\Filters\Filter::make('expired')
          ->label('Expirés')
          ->query(
            fn(Builder $query): Builder =>
            $query->where('end_date', '<', now())
          )
          ->toggle(),

        Tables\Filters\Filter::make('active_only')
          ->label('Contrats actifs uniquement')
          ->query(
            fn(Builder $query): Builder =>
            $query->where('status', ContractStatus::ACTIVE)
          )
          ->toggle(),

        Tables\Filters\Filter::make('auto_renewal')
          ->label('Renouvellement automatique')
          ->query(
            fn(Builder $query): Builder =>
            $query->where('auto_renewal', true)
          )
          ->toggle(),
      ])
      ->headerActions([
        // No create action since owners cannot create contracts
      ])
      ->actions([
        Tables\Actions\ViewAction::make()
          ->label('Voir')
          ->icon('heroicon-o-eye'),

        Tables\Actions\Action::make('download_pdf')
          ->label('Télécharger PDF')
          ->icon('heroicon-o-document-arrow-down')
          ->color('info')
          ->url(function (Contract $record): string {
            // Validate access before generating URL
            $user = auth()->user();
            if (!$user || !$this->validateContractAccess($record, $user)) {
              return '#';
            }

            return route('contracts.pdf', $record);
          })
          ->openUrlInNewTab()
          ->visible(function (Contract $record): bool {
            $user = auth()->user();
            return $user && $this->validateContractAccess($record, $user);
          }),

        Tables\Actions\Action::make('view_pdf')
          ->label('Voir PDF')
          ->icon('heroicon-o-document-magnifying-glass')
          ->color('gray')
          ->url(function (Contract $record): string {
            // Validate access before generating URL
            $user = auth()->user();
            if (!$user || !$this->validateContractAccess($record, $user)) {
              return '#';
            }

            return route('contracts.pdf.view', $record);
          })
          ->openUrlInNewTab()
          ->visible(function (Contract $record): bool {
            $user = auth()->user();
            return $user && $this->validateContractAccess($record, $user);
          }),
      ])
      ->bulkActions([
        // No bulk actions for security reasons
      ])
      ->defaultSort('created_at', 'desc')
      ->striped()
      ->emptyStateHeading('Aucun contrat trouvé')
      ->emptyStateDescription('Ce locataire n\'a actuellement aucun contrat dans vos propriétés.')
      ->emptyStateIcon('heroicon-o-document-text');
  }

  /**
   * Validate that a contract belongs to the authenticated owner
   */
  private function validateContractAccess(Contract $contract, ?\App\Models\User $user = null): bool
  {
    if ($user === null) {
      $user = auth()->user();
    }

    if (!$user) {
      return false;
    }

    try {
      return $contract->property()
        ->whereHas('owner', function (Builder $ownerQuery) use ($user) {
          // Primary check: Direct user_id relation
          $ownerQuery->where('user_id', $user->id);
        })
        // Fallback: Email matching
        ->orWhereHas('owner', function (Builder $ownerQuery) use ($user) {
          $ownerQuery->where('email', $user->email);
        })
        // Additional fallback: Name matching
        ->orWhereHas('owner', function (Builder $ownerQuery) use ($user) {
          $ownerQuery->where('name', 'like', '%' . trim($user->name) . '%')
            ->whereNotNull('name')
            ->where('name', '!=', '');
        })
        ->exists();
    } catch (\Exception $e) {
      \Log::warning('Error validating contract access', [
        'user_id' => $user->id,
        'contract_id' => $contract->id,
        'error' => $e->getMessage(),
      ]);
      return false;
    }
  }

  /**
   * Override to add security validation
   */
  public function canView(Model $record): bool
  {
    $user = auth()->user();

    if (!$user) {
      return false;
    }

    // Check if user can access owner panel
    if (!$user->canAccessOwnerPanel()) {
      return false;
    }

    // Validate contract access
    return $this->validateContractAccess($record, $user);
  }

  /**
   * Owners cannot create contracts
   */
  public function canCreate(): bool
  {
    return false;
  }

  /**
   * Owners cannot edit contracts
   */
  public function canEdit(Model $record): bool
  {
    return false;
  }

  /**
   * Owners cannot delete contracts
   */
  public function canDelete(Model $record): bool
  {
    return false;
  }
}
