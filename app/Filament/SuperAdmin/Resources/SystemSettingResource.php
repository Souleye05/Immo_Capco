<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\SystemSettingResource\Pages;
use App\Models\SystemSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SystemSettingResource extends Resource
{
  protected static ?string $model = SystemSetting::class;

  protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

  protected static ?string $navigationLabel = 'System Settings';

  protected static ?string $modelLabel = 'System Setting';

  protected static ?string $pluralModelLabel = 'System Settings';

  protected static ?string $recordTitleAttribute = 'key';

  protected static ?int $navigationSort = 3;

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Section::make('Setting Information')
          ->schema([
            Forms\Components\TextInput::make('key')
              ->required()
              ->unique(ignoreRecord: true)
              ->maxLength(255)
              ->helperText('Unique identifier for this setting')
              ->columnSpanFull(),

            Forms\Components\Select::make('group')
              ->required()
              ->options([
                'general' => 'General',
                'platform' => 'Platform',
                'maintenance' => 'Maintenance',
                'notifications' => 'Notifications',
                'security' => 'Security',
                'performance' => 'Performance',
                'integrations' => 'Integrations',
                'billing' => 'Billing',
              ])
              ->default('general')
              ->searchable(),

            Forms\Components\Select::make('type')
              ->required()
              ->options([
                'string' => 'String',
                'number' => 'Number',
                'boolean' => 'Boolean',
                'json' => 'JSON',
                'array' => 'Array',
                'email' => 'Email',
                'url' => 'URL',
                'date' => 'Date',
                'datetime' => 'DateTime',
              ])
              ->default('string')
              ->live()
              ->afterStateUpdated(fn(Forms\Set $set) => $set('value', null)),

            Forms\Components\Toggle::make('is_public')
              ->label('Public Setting')
              ->helperText('Public settings can be accessed by frontend applications')
              ->default(false),
          ])
          ->columns(2),

        Forms\Components\Section::make('Setting Value')
          ->schema([
            Forms\Components\Textarea::make('description')
              ->maxLength(1000)
              ->rows(3)
              ->columnSpanFull(),

            // Dynamic value field based on type
            Forms\Components\TextInput::make('value')
              ->label('Value')
              ->required()
              ->visible(fn(Forms\Get $get): bool => in_array($get('type'), ['string', 'email', 'url']))
              ->email(fn(Forms\Get $get): bool => $get('type') === 'email')
              ->url(fn(Forms\Get $get): bool => $get('type') === 'url')
              ->columnSpanFull(),

            Forms\Components\TextInput::make('value')
              ->label('Value')
              ->required()
              ->numeric()
              ->visible(fn(Forms\Get $get): bool => $get('type') === 'number')
              ->columnSpanFull(),

            Forms\Components\Toggle::make('value')
              ->label('Value')
              ->visible(fn(Forms\Get $get): bool => $get('type') === 'boolean')
              ->columnSpanFull(),

            Forms\Components\DatePicker::make('value')
              ->label('Value')
              ->required()
              ->visible(fn(Forms\Get $get): bool => $get('type') === 'date')
              ->columnSpanFull(),

            Forms\Components\DateTimePicker::make('value')
              ->label('Value')
              ->required()
              ->visible(fn(Forms\Get $get): bool => $get('type') === 'datetime')
              ->columnSpanFull(),

            Forms\Components\Textarea::make('value')
              ->label('Value (JSON)')
              ->required()
              ->visible(fn(Forms\Get $get): bool => in_array($get('type'), ['json', 'array']))
              ->helperText('Enter valid JSON format')
              ->rows(5)
              ->columnSpanFull(),
          ]),
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('key')
          ->searchable()
          ->sortable()
          ->weight('bold')
          ->copyable(),

        Tables\Columns\TextColumn::make('group')
          ->searchable()
          ->sortable()
          ->badge()
          ->color(fn(string $state): string => match ($state) {
            'general' => 'gray',
            'platform' => 'primary',
            'maintenance' => 'warning',
            'notifications' => 'info',
            'security' => 'danger',
            'performance' => 'success',
            'integrations' => 'purple',
            'billing' => 'orange',
            default => 'gray',
          }),

        Tables\Columns\TextColumn::make('type')
          ->searchable()
          ->sortable()
          ->badge()
          ->color('secondary'),

        Tables\Columns\TextColumn::make('formatted_value')
          ->label('Value')
          ->limit(50)
          ->tooltip(function (Model $record): ?string {
            $value = $record->value;
            if (is_array($value)) {
              return json_encode($value, JSON_PRETTY_PRINT);
            }
            return is_string($value) ? $value : json_encode($value);
          }),

        Tables\Columns\IconColumn::make('is_public')
          ->label('Public')
          ->boolean()
          ->sortable(),

        Tables\Columns\TextColumn::make('description')
          ->limit(30)
          ->tooltip(fn(Model $record): ?string => $record->description)
          ->toggleable(),

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
        Tables\Filters\SelectFilter::make('group')
          ->options([
            'general' => 'General',
            'platform' => 'Platform',
            'maintenance' => 'Maintenance',
            'notifications' => 'Notifications',
            'security' => 'Security',
            'performance' => 'Performance',
            'integrations' => 'Integrations',
            'billing' => 'Billing',
          ])
          ->multiple(),

        Tables\Filters\SelectFilter::make('type')
          ->options([
            'string' => 'String',
            'number' => 'Number',
            'boolean' => 'Boolean',
            'json' => 'JSON',
            'array' => 'Array',
            'email' => 'Email',
            'url' => 'URL',
            'date' => 'Date',
            'datetime' => 'DateTime',
          ])
          ->multiple(),

        Tables\Filters\TernaryFilter::make('is_public')
          ->label('Public Settings')
          ->placeholder('All settings')
          ->trueLabel('Public only')
          ->falseLabel('Private only'),
      ])
      ->actions([
        Tables\Actions\ViewAction::make(),
        Tables\Actions\EditAction::make(),
        Tables\Actions\DeleteAction::make()
          ->requiresConfirmation()
          ->modalDescription('Are you sure you want to delete this system setting? This action cannot be undone.'),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DeleteBulkAction::make()
            ->requiresConfirmation(),
        ]),
      ])
      ->defaultSort('group')
      ->groups([
        Tables\Grouping\Group::make('group')
          ->label('Group')
          ->collapsible(),
      ]);
  }

  public static function infolist(Infolist $infolist): Infolist
  {
    return $infolist
      ->schema([
        Infolists\Components\Section::make('Setting Information')
          ->schema([
            Infolists\Components\TextEntry::make('key')
              ->size('lg')
              ->weight('bold')
              ->copyable(),

            Infolists\Components\TextEntry::make('group')
              ->badge()
              ->color(fn(string $state): string => match ($state) {
                'general' => 'gray',
                'platform' => 'primary',
                'maintenance' => 'warning',
                'notifications' => 'info',
                'security' => 'danger',
                'performance' => 'success',
                'integrations' => 'purple',
                'billing' => 'orange',
                default => 'gray',
              }),

            Infolists\Components\TextEntry::make('type')
              ->badge()
              ->color('secondary'),

            Infolists\Components\IconEntry::make('is_public')
              ->label('Public Setting')
              ->boolean(),

            Infolists\Components\TextEntry::make('created_at')
              ->dateTime(),

            Infolists\Components\TextEntry::make('updated_at')
              ->dateTime(),
          ])
          ->columns(2),

        Infolists\Components\Section::make('Setting Value & Description')
          ->schema([
            Infolists\Components\TextEntry::make('description')
              ->placeholder('No description provided')
              ->columnSpanFull(),

            Infolists\Components\TextEntry::make('value')
              ->label('Current Value')
              ->formatStateUsing(function ($state, Model $record): string {
                if (is_array($state)) {
                  return json_encode($state, JSON_PRETTY_PRINT);
                }

                return match ($record->type) {
                  'boolean' => $state ? 'Yes' : 'No',
                  'date' => $state ? date('Y-m-d', strtotime($state)) : 'Not set',
                  'datetime' => $state ? date('Y-m-d H:i:s', strtotime($state)) : 'Not set',
                  'number' => is_numeric($state) ? number_format($state) : $state,
                  default => $state ?? 'Not set',
                };
              })
              ->copyable()
              ->columnSpanFull(),
          ]),
      ]);
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListSystemSettings::route('/'),
      'create' => Pages\CreateSystemSetting::route('/create'),
      'view' => Pages\ViewSystemSetting::route('/{record}'),
      'edit' => Pages\EditSystemSetting::route('/{record}/edit'),
    ];
  }

  // Override the query to show all settings (no tenant scoping)
  public static function getEloquentQuery(): Builder
  {
    return parent::getEloquentQuery()->withoutGlobalScopes();
  }
}
