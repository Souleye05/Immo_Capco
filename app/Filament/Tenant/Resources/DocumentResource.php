<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\DocumentResource\Pages;
use App\Models\Contract;
use App\Models\Payment;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DocumentResource extends Resource
{
  // Using Contract as base model but we'll override the query
  protected static ?string $model = Contract::class;

  protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

  protected static ?string $navigationLabel = 'Mes Documents';

  protected static ?string $modelLabel = 'Document';

  protected static ?string $pluralModelLabel = 'Documents';

  protected static ?int $navigationSort = 3;

  public static function getEloquentQuery(): Builder
  {
    // Filter contracts for the authenticated tenant user
    $user = auth()->user();

    return parent::getEloquentQuery()
      ->whereHas('tenant', function (Builder $query) use ($user) {
        // Match by name or email - adjust this logic based on your authentication system
        $query->where('name', 'like', '%' . $user->name . '%')
          ->orWhere('phone', 'like', '%' . $user->email . '%');
      })
      ->with(['tenant', 'property', 'flat', 'agency', 'payments']);
  }

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        // No form - this is a read-only resource
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('document_type')
          ->label('Type de document')
          ->getStateUsing(fn(Contract $record): string => 'Contrat')
          ->badge()
          ->color('primary'),

        TextColumn::make('contract_number')
          ->label('Référence')
          ->searchable()
          ->sortable(),

        TextColumn::make('property.name')
          ->label('Propriété')
          ->searchable()
          ->sortable(),

        TextColumn::make('flat.type')
          ->label('Logement')
          ->searchable()
          ->sortable(),

        TextColumn::make('start_date')
          ->label('Date début')
          ->date('d/m/Y')
          ->sortable(),

        TextColumn::make('end_date')
          ->label('Date fin')
          ->date('d/m/Y')
          ->sortable(),

        BadgeColumn::make('status')
          ->label('Statut')
          ->colors([
            'success' => 'active',
            'warning' => 'pending',
            'danger' => 'terminated',
            'secondary' => 'expired',
          ])
          ->formatStateUsing(fn($state): string => match ($state->value ?? $state) {
            'active' => 'Actif',
            'pending' => 'En attente',
            'terminated' => 'Résilié',
            'expired' => 'Expiré',
            default => 'Inconnu',
          }),

        TextColumn::make('created_at')
          ->label('Créé le')
          ->date('d/m/Y H:i')
          ->sortable(),
      ])
      ->filters([
        SelectFilter::make('status')
          ->label('Statut du contrat')
          ->options([
            'active' => 'Actif',
            'pending' => 'En attente',
            'terminated' => 'Résilié',
            'expired' => 'Expiré',
          ]),

        SelectFilter::make('agency_id')
          ->label('Agence')
          ->relationship('agency', 'name'),

        Filter::make('recent')
          ->label('Documents récents')
          ->query(
            fn(Builder $query): Builder =>
            $query->where('created_at', '>=', Carbon::now()->subMonths(3))
          ),
      ])
      ->actions([
        Tables\Actions\ActionGroup::make([
          Tables\Actions\ViewAction::make()
            ->label('Voir détails'),

          Tables\Actions\Action::make('download_contract')
            ->label('Télécharger Contrat')
            ->icon('heroicon-o-document-text')
            ->color('primary')
            ->visible(fn(Contract $record): bool => true) // Always show for contracts
            ->action(function (Contract $record) {
              // This would typically generate and download the contract PDF
              // For now, we'll show a notification
              \Filament\Notifications\Notification::make()
                ->title('Téléchargement du contrat')
                ->body('Le téléchargement du contrat ' . $record->contract_number . ' va commencer.')
                ->success()
                ->send();
            }),

          Tables\Actions\Action::make('view_payments')
            ->label('Voir Paiements')
            ->icon('heroicon-o-credit-card')
            ->color('success')
            ->url(
              fn(Contract $record) =>
              route('filament.tenant.resources.payments.index', [
                'tenant' => $record->agency->slug,
                'tableFilters' => [
                    'contract_id' => ['value' => $record->id],
                ]
            ])
            
            ),
        ]),
      ])
      ->bulkActions([
        // No bulk actions for tenant panel
      ])
      ->defaultSort('created_at', 'desc')
      ->striped()
      ->paginated([10, 25, 50])
      ->emptyStateHeading('Aucun document trouvé')
      ->emptyStateDescription('Vous n\'avez aucun document disponible.')
      ->emptyStateIcon('heroicon-o-document-duplicate');
  }

  public static function getRelations(): array
  {
    return [
      // Add relation managers if needed
    ];
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListDocuments::route('/'),
      'view' => Pages\ViewDocument::route('/{record}'),
    ];
  }

  public static function canCreate(): bool
  {
    return false; // Tenants cannot create documents
  }

  public static function canEdit(Model $record): bool
  {
    return false; // Tenants cannot edit documents
  }

  public static function canDelete(Model $record): bool
  {
    return false; // Tenants cannot delete documents
  }

  public static function getNavigationBadge(): ?string
  {
    // Show number of active contracts
    $activeCount = static::getEloquentQuery()
      ->where('status', 'active')
      ->count();

    return $activeCount > 0 ? (string) $activeCount : null;
  }

  public static function getNavigationBadgeColor(): ?string
  {
    return static::getNavigationBadge() ? 'success' : null;
  }
}
