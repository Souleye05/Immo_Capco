<?php

namespace App\Filament\SuperAdmin\Resources\GlobalUserResource\RelationManagers;

use App\Models\Agency;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AgencysRelationManager extends RelationManager
{
  protected static string $relationship = 'agencys';

  protected static ?string $title = 'User Agencies';

  protected static ?string $modelLabel = 'Agency';

  protected static ?string $pluralModelLabel = 'Agencies';

  public function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Section::make('Agency Information')
          ->schema([
            Forms\Components\TextInput::make('name')
              ->required()
              ->maxLength(255),
            Forms\Components\TextInput::make('slug')
              ->required()
              ->unique(Agency::class, 'slug', ignoreRecord: true)
              ->maxLength(255)
              ->helperText('Used in URLs for tenant identification'),
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
        Tables\Columns\TextColumn::make('slug')
          ->searchable()
          ->sortable()
          ->color('gray'),
        Tables\Columns\TextColumn::make('members_count')
          ->counts('members')
          ->label('Total Users')
          ->badge()
          ->color('primary'),
        Tables\Columns\TextColumn::make('tenants_count')
          ->counts('tenants')
          ->label('Tenants')
          ->badge()
          ->color('success'),
        Tables\Columns\TextColumn::make('created_at')
          ->label('Created')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        Tables\Filters\Filter::make('has_tenants')
          ->label('Has Tenants')
          ->query(fn(Builder $query): Builder => $query->has('tenants')),
        Tables\Filters\Filter::make('created_this_month')
          ->label('Created This Month')
          ->query(fn(Builder $query): Builder => $query->whereMonth('created_at', now()->month)),
      ])
      ->headerActions([
        Tables\Actions\CreateAction::make()
          ->modalHeading('Create New Agency for User'),
        Tables\Actions\AttachAction::make()
          ->preloadRecordSelect()
          ->modalHeading('Attach User to Existing Agency')
          ->recordSelectSearchColumns(['name', 'slug']),
      ])
      ->actions([
        Tables\Actions\ViewAction::make(),
        Tables\Actions\EditAction::make(),
        Tables\Actions\DetachAction::make()
          ->requiresConfirmation()
          ->modalDescription('This will remove the user from this agency but will not delete the agency.'),
        Tables\Actions\DeleteAction::make()
          ->requiresConfirmation()
          ->modalDescription('This will permanently delete the agency and all related data. This action cannot be undone.'),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DetachBulkAction::make()
            ->requiresConfirmation(),
          Tables\Actions\DeleteBulkAction::make()
            ->requiresConfirmation(),
        ]),
      ])
      ->defaultSort('agencies.created_at', 'desc');
  }
}
