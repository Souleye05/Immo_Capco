<?php

namespace App\Filament\SuperAdmin\Resources\AgencyResource\RelationManagers;

use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TenantsRelationManager extends RelationManager
{
  protected static string $relationship = 'tenants';

  protected static ?string $title = 'Agency Tenants';

  protected static ?string $modelLabel = 'Tenant';

  protected static ?string $pluralModelLabel = 'Tenants';

  public function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Section::make('Tenant Information')
          ->schema([
            Forms\Components\TextInput::make('name')
              ->required()
              ->maxLength(255),
            Forms\Components\TextInput::make('phone')
              ->tel()
              ->maxLength(255),
            Forms\Components\Textarea::make('address')
              ->maxLength(500)
              ->rows(3),
            Forms\Components\Select::make('flat_id')
              ->label('Flat')
              ->relationship('flatThroughContract', 'id')
              ->getOptionLabelFromRecordUsing(fn($record) => "Flat #{$record->id} - {$record->property->name}")
              ->searchable()
              ->preload(),
          ])
          ->columns(2),
      ]);
  }

  public function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('name')
      ->columns([
        Tables\Columns\TextColumn::make('name')
          ->searchable()
          ->sortable()
          ->weight('bold'),
        Tables\Columns\TextColumn::make('phone')
          ->searchable()
          ->sortable()
          ->copyable(),
        Tables\Columns\TextColumn::make('address')
          ->searchable()
          ->limit(50)
          ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
            $state = $column->getState();
            return strlen($state) > 50 ? $state : null;
          }),
        Tables\Columns\TextColumn::make('activeContract.flat.property.name')
          ->label('Property')
          ->searchable()
          ->sortable()
          ->placeholder('No active contract'),
        Tables\Columns\TextColumn::make('activeContract.monthly_rent')
          ->label('Monthly Rent')
          ->money('EUR')
          ->sortable()
          ->placeholder('No active contract'),
        Tables\Columns\TextColumn::make('contracts_count')
          ->counts('contracts')
          ->label('Total Contracts')
          ->badge()
          ->color('info'),
        Tables\Columns\TextColumn::make('created_at')
          ->label('Added')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        Tables\Filters\Filter::make('has_active_contract')
          ->label('Has Active Contract')
          ->query(fn(Builder $query): Builder => $query->whereHas('activeContract')),
        Tables\Filters\Filter::make('no_active_contract')
          ->label('No Active Contract')
          ->query(fn(Builder $query): Builder => $query->whereDoesntHave('activeContract')),
        Tables\Filters\Filter::make('created_this_month')
          ->label('Added This Month')
          ->query(fn(Builder $query): Builder => $query->whereMonth('created_at', now()->month)),
      ])
      ->headerActions([
        Tables\Actions\CreateAction::make()
          ->modalHeading('Add Tenant to Agency'),
      ])
      ->actions([
        Tables\Actions\ViewAction::make(),
        Tables\Actions\EditAction::make(),
        Tables\Actions\DeleteAction::make()
          ->requiresConfirmation()
          ->modalDescription('This will permanently delete the tenant and all related data. This action cannot be undone.'),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DeleteBulkAction::make()
            ->requiresConfirmation(),
        ]),
      ])
      ->defaultSort('created_at', 'desc');
  }
}
