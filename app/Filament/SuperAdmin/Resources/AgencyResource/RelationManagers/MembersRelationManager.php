<?php

namespace App\Filament\SuperAdmin\Resources\AgencyResource\RelationManagers;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MembersRelationManager extends RelationManager
{
  protected static string $relationship = 'members';

  protected static ?string $title = 'Agency Users';

  protected static ?string $modelLabel = 'User';

  protected static ?string $pluralModelLabel = 'Users';

  public function form(Form $form): Form
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
              ->unique(User::class, 'email', ignoreRecord: true)
              ->maxLength(255),
            Forms\Components\TextInput::make('password')
              ->password()
              ->required(fn(string $context): bool => $context === 'create')
              ->minLength(8)
              ->dehydrated(fn($state) => filled($state))
              ->dehydrateStateUsing(fn($state) => bcrypt($state)),
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
        Tables\Columns\TextColumn::make('email')
          ->searchable()
          ->sortable()
          ->copyable(),
        Tables\Columns\TextColumn::make('email_verified_at')
          ->label('Verified')
          ->dateTime()
          ->sortable()
          ->toggleable()
          ->placeholder('Not verified')
          ->color(fn($state) => $state ? 'success' : 'danger'),
        Tables\Columns\TextColumn::make('created_at')
          ->label('Joined')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        Tables\Filters\Filter::make('verified')
          ->label('Verified Users')
          ->query(fn(Builder $query): Builder => $query->whereNotNull('email_verified_at')),
        Tables\Filters\Filter::make('unverified')
          ->label('Unverified Users')
          ->query(fn(Builder $query): Builder => $query->whereNull('email_verified_at')),
      ])
      ->headerActions([
        Tables\Actions\CreateAction::make()
          ->modalHeading('Add User to Agency'),
        Tables\Actions\AttachAction::make()
          ->preloadRecordSelect()
          ->modalHeading('Attach Existing User to Agency')
          ->recordSelectSearchColumns(['name', 'email']),
      ])
      ->actions([
        Tables\Actions\EditAction::make(),
        Tables\Actions\DetachAction::make()
          ->requiresConfirmation()
          ->modalDescription('This will remove the user from this agency but will not delete the user account.'),
        Tables\Actions\DeleteAction::make()
          ->requiresConfirmation()
          ->modalDescription('This will permanently delete the user account. This action cannot be undone.'),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DetachBulkAction::make()
            ->requiresConfirmation(),
          Tables\Actions\DeleteBulkAction::make()
            ->requiresConfirmation(),
        ]),
      ])
      ->defaultSort('name', 'asc');
  }
}
