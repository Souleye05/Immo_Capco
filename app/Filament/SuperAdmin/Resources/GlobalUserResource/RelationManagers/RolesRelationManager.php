<?php

namespace App\Filament\SuperAdmin\Resources\GlobalUserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class RolesRelationManager extends RelationManager
{
  protected static string $relationship = 'roles';

  protected static ?string $title = 'User Roles';

  protected static ?string $modelLabel = 'Role';

  protected static ?string $pluralModelLabel = 'Roles';

  public function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Section::make('Role Information')
          ->schema([
            Forms\Components\TextInput::make('name')
              ->required()
              ->maxLength(255)
              ->unique(Role::class, 'name', ignoreRecord: true),
            Forms\Components\TextInput::make('guard_name')
              ->default('web')
              ->required()
              ->maxLength(255),
            Forms\Components\Select::make('permissions')
              ->relationship('permissions', 'name')
              ->multiple()
              ->preload()
              ->searchable()
              ->helperText('Select permissions for this role'),
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
        Tables\Columns\TextColumn::make('guard_name')
          ->label('Guard')
          ->badge()
          ->color('gray'),
        Tables\Columns\TextColumn::make('permissions_count')
          ->counts('permissions')
          ->label('Permissions')
          ->badge()
          ->color('info'),
        Tables\Columns\TextColumn::make('users_count')
          ->counts('users')
          ->label('Users')
          ->badge()
          ->color('success'),
        Tables\Columns\TextColumn::make('created_at')
          ->label('Created')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('guard_name')
          ->label('Guard')
          ->options([
            'web' => 'Web',
            'api' => 'API',
          ]),
        Tables\Filters\Filter::make('has_permissions')
          ->label('Has Permissions')
          ->query(fn(Builder $query): Builder => $query->has('permissions')),
      ])
      ->headerActions([
        Tables\Actions\CreateAction::make()
          ->modalHeading('Create New Role for User'),
        Tables\Actions\AttachAction::make()
          ->preloadRecordSelect()
          ->modalHeading('Assign Existing Role to User')
          ->recordSelectSearchColumns(['name']),
      ])
      ->actions([
        Tables\Actions\ViewAction::make(),
        Tables\Actions\EditAction::make(),
        Tables\Actions\DetachAction::make()
          ->requiresConfirmation()
          ->modalDescription('This will remove the role from this user but will not delete the role.'),
        Tables\Actions\DeleteAction::make()
          ->requiresConfirmation()
          ->modalDescription('This will permanently delete the role and remove it from all users. This action cannot be undone.'),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DetachBulkAction::make()
            ->requiresConfirmation(),
          Tables\Actions\DeleteBulkAction::make()
            ->requiresConfirmation(),
        ]),
      ])
      ->defaultSort('created_at', 'desc');
  }
}
