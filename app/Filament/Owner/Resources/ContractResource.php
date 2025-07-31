<?php

namespace App\Filament\Owner\Resources;

use App\Enums\ContractStatus;
use App\Filament\Owner\Resources\ContractResource\Pages;
use App\Models\Contract;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ContractResource extends Resource
{
  protected static ?string $model = Contract::class;

  protected static ?string $navigationIcon = 'heroicon-o-document-text';
  protected static ?string $navigationGroup = 'Mes Biens';
  protected static ?string $navigationLabel = 'Mes Contrats';
  protected static ?string $modelLabel = 'Contrat';
  protected static ?string $pluralModelLabel = 'Contrats';

  // Override the tenant ownership relationship for this resource
  protected static ?string $tenantOwnershipRelationshipName = 'agency';

  public static function getEloquentQuery(): Builder
  {
    // Filter contracts to show only those for properties owned by the authenticated user
    // Find the owner record that matches the authenticated user's name
    $user = auth()->user();
    $owner = \App\Models\Owner::where('name', $user->name)->first();

    if (!$owner) {
      // If no matching owner found, return empty query
      return parent::getEloquentQuery()->whereRaw('1 = 0');
    }

    return parent::getEloquentQuery()
      ->whereHas('property', function (Builder $query) use ($owner) {
        $query->where('owner_id', $owner->id);
      })
      ->with(['tenant', 'property', 'flat']);
  }

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Section::make('Informations du contrat')
          ->schema([
            Forms\Components\TextInput::make('contract_number')
              ->label('N° Contrat')
              ->disabled()
              ->dehydrated(false),

            Forms\Components\Select::make('status')
              ->label('Statut')
              ->options(ContractStatus::options())
              ->disabled()
              ->dehydrated(false),
          ])
          ->columns(2),

        Forms\Components\Section::make('Informations du bien')
          ->schema([
            Forms\Components\TextInput::make('property.name')
              ->label('Propriété')
              ->disabled()
              ->dehydrated(false),

            Forms\Components\TextInput::make('flat.type')
              ->label('Appartement')
              ->disabled()
              ->dehydrated(false)
              ->formatStateUsing(fn($record) => $record?->flat?->type?->label()),
          ])
          ->columns(2),

        Forms\Components\Section::make('Informations du locataire')
          ->schema([
            Forms\Components\TextInput::make('tenant.name')
              ->label('Nom du locataire')
              ->disabled()
              ->dehydrated(false),

            Forms\Components\TextInput::make('tenant.phone')
              ->label('Téléphone')
              ->disabled()
              ->dehydrated(false),
          ])
          ->columns(2),

        Forms\Components\Section::make('Conditions financières')
          ->schema([
            Forms\Components\TextInput::make('monthly_rent')
              ->label('Loyer mensuel')
              ->disabled()
              ->dehydrated(false)
              ->formatStateUsing(fn($state) => number_format($state, 0, ',', ' ') . ' F CFA'),

            Forms\Components\TextInput::make('cautions')
              ->label('Dépôt de garantie')
              ->disabled()
              ->dehydrated(false)
              ->formatStateUsing(fn($state) => number_format($state, 0, ',', ' ') . ' F CFA'),
          ])
          ->columns(2),

        Forms\Components\Section::make('Dates du contrat')
          ->schema([
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
              ->dehydrated(false),
          ])
          ->columns(3),
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('contract_number')
          ->label('N° Contrat')
          ->searchable()
          ->sortable()
          ->copyable(),

        Tables\Columns\TextColumn::make('property.name')
          ->label('Propriété')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('flat.type')
          ->label('Appartement')
          ->formatStateUsing(fn($record) => $record->flat?->type?->label())
          ->badge()
          ->color('gray'),

        Tables\Columns\TextColumn::make('tenant.name')
          ->label('Locataire')
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('monthly_rent')
          ->label('Loyer mensuel')
          ->formatStateUsing(fn($state) => number_format($state, 0, ',', ' ') . ' F CFA')
          ->sortable()
          ->alignEnd(),

        Tables\Columns\TextColumn::make('status')
          ->label('Statut')
          ->badge()
          ->color(fn(ContractStatus $state): string => match ($state) {
            ContractStatus::ACTIVE => 'success',
            ContractStatus::DRAFT => 'gray',
            ContractStatus::EXPIRED => 'warning',
            ContractStatus::TERMINATED => 'danger',
            ContractStatus::RENEWED => 'info',
          })
          ->formatStateUsing(fn(ContractStatus $state) => $state->label()),

        Tables\Columns\TextColumn::make('start_date')
          ->label('Date début')
          ->date('d/m/Y')
          ->sortable(),

        Tables\Columns\TextColumn::make('end_date')
          ->label('Date fin')
          ->date('d/m/Y')
          ->sortable()
          ->color(fn($record) => $record->end_date < Carbon::now() ? 'danger' : 'success'),

        Tables\Columns\TextColumn::make('days_until_expiration')
          ->label('Jours restants')
          ->state(fn($record) => Carbon::now()->diffInDays($record->end_date, false))
          ->formatStateUsing(function ($state) {
            if ($state < 0) {
              return 'Expiré depuis ' . abs($state) . ' jours';
            } elseif ($state == 0) {
              return 'Expire aujourd\'hui';
            } else {
              return $state . ' jours';
            }
          })
          ->color(function ($state) {
            if ($state < 0) return 'danger';
            if ($state <= 30) return 'warning';
            return 'success';
          })
          ->badge(),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('status')
          ->label('Statut')
          ->options(ContractStatus::options())
          ->multiple(),

        Tables\Filters\SelectFilter::make('property_id')
          ->label('Propriété')
          ->relationship('property', 'name')
          ->searchable()
          ->preload(),

        Tables\Filters\Filter::make('expiring_soon')
          ->label('Expire bientôt')
          ->query(
            fn(Builder $query): Builder =>
            $query->where('end_date', '<=', Carbon::now()->addDays(90))
              ->where('end_date', '>', Carbon::now())
              ->where('status', ContractStatus::ACTIVE)
          )
          ->toggle(),

        Tables\Filters\Filter::make('expired')
          ->label('Expirés')
          ->query(
            fn(Builder $query): Builder =>
            $query->where('end_date', '<', Carbon::now())
              ->where('status', ContractStatus::ACTIVE)
          )
          ->toggle(),
      ])
      ->actions([
        Tables\Actions\ViewAction::make(),

        Tables\Actions\Action::make('download_contract')
          ->label('Télécharger')
          ->icon('heroicon-o-document-arrow-down')
          ->color('info')
          ->url(fn(Contract $record): string => route('contracts.pdf', $record))
          ->openUrlInNewTab(),
      ])
      ->defaultSort('created_at', 'desc')
      ->striped();
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListContracts::route('/'),
      'view' => Pages\ViewContract::route('/{record}'),
    ];
  }

  public static function canCreate(): bool
  {
    return false; // Owners cannot create contracts
  }

  public static function canEdit($record): bool
  {
    return false; // Owners cannot edit contracts
  }

  public static function canDelete($record): bool
  {
    return false; // Owners cannot delete contracts
  }
}
