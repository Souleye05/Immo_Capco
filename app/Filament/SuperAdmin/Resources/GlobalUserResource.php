<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\GlobalUserResource\Pages;
use App\Filament\SuperAdmin\Resources\GlobalUserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class GlobalUserResource extends Resource
{
  protected static ?string $model = User::class;

  protected static ?string $navigationIcon = 'heroicon-o-users';

  protected static ?string $navigationLabel = 'All Users';

  protected static ?string $modelLabel = 'User';

  protected static ?string $pluralModelLabel = 'Users';

  protected static ?string $recordTitleAttribute = 'name';

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Section::make('User Information')
          ->schema([
            Forms\Components\TextInput::make('name')
              ->required()
              ->maxLength(255),
            Forms\Components\TextInput::make('email')
              ->email()
              ->required()
              ->unique(ignoreRecord: true)
              ->maxLength(255),
            Forms\Components\TextInput::make('password')
              ->password()
              ->required(fn(string $context): bool => $context === 'create')
              ->minLength(8)
              ->dehydrated(fn($state) => filled($state))
              ->dehydrateStateUsing(fn($state) => bcrypt($state)),
          ])
          ->columns(2),

        Forms\Components\Section::make('Agency Access')
          ->schema([
            Forms\Components\Select::make('agencys')
              ->relationship('agencys', 'name', fn(Builder $query) => $query->withoutGlobalScopes())
              ->multiple()
              ->preload()
              ->searchable()
              ->helperText('Select agencies this user can access')
              ->columnSpanFull(),
          ]),

        Forms\Components\Section::make('Roles & Permissions')
          ->schema([
            Forms\Components\Select::make('roles')
              ->relationship('roles', 'name')
              ->multiple()
              ->preload()
              ->searchable()
              ->helperText('Select roles for this user')
              ->columnSpanFull(),
          ])
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('name')
          ->searchable()
          ->sortable()
          ->weight('bold'),
        Tables\Columns\TextColumn::make('email')
          ->searchable()
          ->sortable()
          ->copyable(),
        Tables\Columns\TextColumn::make('agencys.name')
          ->badge()
          ->label('Agencies')
          ->searchable()
          ->color('primary')
          ->separator(','),
        Tables\Columns\TextColumn::make('roles.name')
          ->badge()
          ->label('Roles')
          ->searchable()
          ->color('success')
          ->separator(','),
        Tables\Columns\TextColumn::make('email_verified_at')
          ->label('Verified')
          ->dateTime()
          ->sortable()
          ->toggleable()
          ->placeholder('Not verified')
          ->color(fn($state) => $state ? 'success' : 'danger'),
        Tables\Columns\TextColumn::make('agencys_count')
          ->counts('agencys')
          ->label('Agency Count')
          ->badge()
          ->color('info'),
        Tables\Columns\TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('updated_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('agency')
          ->relationship('agencys', 'name', fn(Builder $query) => $query->withoutGlobalScopes())
          ->searchable()
          ->preload()
          ->multiple(),

        Tables\Filters\Filter::make('verified')
          ->label('Verified Users')
          ->query(fn(Builder $query): Builder => $query->whereNotNull('email_verified_at')),
        Tables\Filters\Filter::make('unverified')
          ->label('Unverified Users')
          ->query(fn(Builder $query): Builder => $query->whereNull('email_verified_at')),
        Tables\Filters\Filter::make('has_agencies')
          ->label('Has Agency Access')
          ->query(fn(Builder $query): Builder => $query->has('agencys')),
        Tables\Filters\Filter::make('no_agencies')
          ->label('No Agency Access')
          ->query(fn(Builder $query): Builder => $query->doesntHave('agencys')),
        Tables\Filters\Filter::make('created_this_month')
          ->label('Created This Month')
          ->query(fn(Builder $query): Builder => $query->whereMonth('created_at', now()->month)),
      ])
      ->actions([
        Tables\Actions\ViewAction::make(),
        Tables\Actions\EditAction::make(),
        Tables\Actions\DeleteAction::make()
          ->requiresConfirmation()
          ->modalDescription('This will permanently delete the user account and remove them from all agencies. This action cannot be undone.'),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DeleteBulkAction::make()
            ->requiresConfirmation(),
        ]),
      ])
      ->defaultSort('users.created_at', 'desc');
  }

  public static function infolist(Infolist $infolist): Infolist
  {
    return $infolist
      ->schema([
        Infolists\Components\Section::make('User Information')
          ->schema([
            Infolists\Components\TextEntry::make('name')
              ->size('lg')
              ->weight('bold'),
            Infolists\Components\TextEntry::make('email')
              ->copyable()
              ->color('gray'),
            Infolists\Components\TextEntry::make('email_verified_at')
              ->label('Email Verified')
              ->dateTime()
              ->placeholder('Not verified')
              ->color(fn($state) => $state ? 'success' : 'danger'),
            Infolists\Components\TextEntry::make('created_at')
              ->dateTime(),
            Infolists\Components\TextEntry::make('updated_at')
              ->dateTime(),
          ])
          ->columns(2),

        Infolists\Components\Section::make('Agency Access')
          ->schema([
            Infolists\Components\TextEntry::make('agencys.name')
              ->label('Agencies')
              ->badge()
              ->color('primary')
              ->separator(',')
              ->placeholder('No agency access'),
            Infolists\Components\TextEntry::make('agencys_count')
              ->label('Total Agencies')
              ->getStateUsing(fn(Model $record): int => $record->agencys()->count())
              ->badge()
              ->color('info'),
          ])
          ->columns(2),

        Infolists\Components\Section::make('Roles & Permissions')
          ->schema([
            Infolists\Components\TextEntry::make('roles.name')
              ->label('Roles')
              ->badge()
              ->color('success')
              ->separator(',')
              ->placeholder('No roles assigned'),
          ])
      ]);
  }

  public static function getRelations(): array
  {
    return [
      RelationManagers\AgencysRelationManager::class,
      // RolesRelationManager will be added when Spatie Permission is installed
    ];
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListGlobalUsers::route('/'),
      'create' => Pages\CreateGlobalUser::route('/create'),
      'view' => Pages\ViewGlobalUser::route('/{record}'),
      'edit' => Pages\EditGlobalUser::route('/{record}/edit'),
    ];
  }

  // Override the query to show all users (no tenant scoping)
  public static function getEloquentQuery(): Builder
  {
    return parent::getEloquentQuery()->withoutGlobalScopes();
  }
}
