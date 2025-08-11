<?php

namespace App\Filament\Owner\Resources;

use App\Enums\PropertyType;
use App\Filament\Owner\Resources\PropertyResource\Pages;
use App\Models\Property;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class PropertyResource extends Resource
{
  protected static ?string $model = Property::class;

  protected static ?string $navigationIcon = 'heroicon-o-building-office';
  protected static ?string $navigationGroup = 'Mes Biens';
  protected static ?string $navigationLabel = 'Mes Propriétés';
  protected static ?string $modelLabel = 'Propriété';
  protected static ?string $pluralModelLabel = 'Propriétés';

  // Use tenant scoping to automatically filter by agency
  protected static ?string $tenantOwnershipRelationshipName = 'agency';

  public static function getEloquentQuery(): Builder
  {
    // Filter properties to show only those owned by the authenticated user
    $user = auth()->user();

    // Validation: Ensure user is authenticated
    if (!$user) {
      throw new \Exception('User must be authenticated to access owner resources');
    }

    return parent::getEloquentQuery()
      ->where(function (Builder $query) use ($user) {
        // Primary: Filter by direct user_id relation
        $query->whereHas('owner', function (Builder $subQuery) use ($user) {
          $subQuery->where('user_id', $user->id);
        })
          // Fallback: Filter by email/name matching (like tenant panel)
          ->orWhereHas('owner', function (Builder $subQuery) use ($user) {
            $subQuery->where('email', $user->email)
              ->orWhere('name', 'like', '%' . trim($user->name) . '%');
          });
      })
      ->with(['owner', 'contracts']);
  }

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Section::make('Informations de la propriété')
          ->schema([
            Forms\Components\Select::make('type')
              ->label('Type de propriété')
              ->options(PropertyType::getOptions())
              ->disabled() // Read-only for owners
              ->dehydrated(false),

            Forms\Components\TextInput::make('name')
              ->label('Nom')
              ->disabled() // Read-only for owners
              ->dehydrated(false),

            Forms\Components\Textarea::make('address')
              ->label('Adresse')
              ->disabled() // Read-only for owners
              ->dehydrated(false)
              ->rows(2),
          ])
          ->columns(2),

        Forms\Components\Section::make('Informations financières')
          ->schema([
            Forms\Components\TextInput::make('commission_value')
              ->label('Commission')
              ->disabled() // Read-only for owners
              ->dehydrated(false)
              ->suffix(fn($record) => $record?->commission_unit?->getLabel() ?? ''),

            Forms\Components\TextInput::make('number_flat')
              ->label("Nombre d'appartements")
              ->disabled() // Read-only for owners
              ->dehydrated(false),
          ])
          ->columns(2),
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('type')
          ->label('Type')
          ->badge()
          ->color(fn(PropertyType $state): string => $state->getColor())
          ->icon(fn(PropertyType $state): string => $state->getIcon())
          ->formatStateUsing(fn(PropertyType $state) => $state->getLabel())
          ->searchable()
          ->sortable(),

        Tables\Columns\TextColumn::make('name')
          ->label('Nom')
          ->searchable()
          ->sortable()
          ->weight('medium'),

        Tables\Columns\TextColumn::make('address')
          ->label('Adresse')
          ->searchable()
          ->limit(50)
          ->tooltip(fn($record) => $record->address),

        Tables\Columns\TextColumn::make('number_flat')
          ->label("Appartements")
          ->alignCenter()
          ->sortable()
          ->badge()
          ->color('gray'),

        Tables\Columns\TextColumn::make('contracts_count')
          ->label('Contrats actifs')
          ->alignCenter()
          ->counts(['contracts' => fn($query) => $query->where('status', \App\Enums\ContractStatus::ACTIVE)])
          ->badge()
          ->color('success'),
      ])
      ->filters([
        self::getPropertyTypeFilter(),
      ])
      ->actions([
        Tables\Actions\ViewAction::make(),
      ])
      ->defaultSort('created_at', 'desc')
      ->striped();
  }

  private static function getPropertyTypeFilter(): Tables\Filters\SelectFilter
  {
    return Tables\Filters\SelectFilter::make('type')
      ->label('Type de propriété')
      ->options(PropertyType::getOptions())
      ->multiple();
  }



  public static function getPages(): array
  {
    return [
      'index' => Pages\ListProperties::route('/'),
      'view' => Pages\ViewProperty::route('/{record}'),
    ];
  }

  public static function canCreate(): bool
  {
    return false; // Owners cannot create properties
  }

  public static function canEdit($record): bool
  {
    return false; // Owners cannot edit properties
  }

  public static function canDelete($record): bool
  {
    return false; // Owners cannot delete properties
  }
}
