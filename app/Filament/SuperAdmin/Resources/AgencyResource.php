<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\AgencyResource\Pages;
use App\Filament\SuperAdmin\Resources\AgencyResource\RelationManagers;
use App\Models\Agency;
use App\Models\Contract;
use App\Models\Owner;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AgencyResource extends Resource
{
  protected static ?string $model = Agency::class;

  protected static ?string $navigationIcon = 'heroicon-o-building-office';

  protected static ?string $navigationLabel = 'Agencies';

  protected static ?string $modelLabel = 'Agency';

  protected static ?string $pluralModelLabel = 'Agencies';

  protected static ?string $recordTitleAttribute = 'name';

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Section::make('Agency Information')
          ->schema([
            Forms\Components\TextInput::make('name')
              ->required()
              ->maxLength(255)
              ->columnSpanFull(),
            Forms\Components\TextInput::make('slug')
              ->required()
              ->unique(ignoreRecord: true)
              ->maxLength(255)
              ->helperText('Used in URLs for tenant identification')
              ->columnSpanFull(),
          ])
          ->columns(2),
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
        Tables\Columns\TextColumn::make('slug')
          ->searchable()
          ->sortable()
          ->color('gray'),
        Tables\Columns\TextColumn::make('members_count')
          ->counts('members')
          ->label('Users')
          ->sortable()
          ->badge()
          ->color('primary'),
        Tables\Columns\TextColumn::make('tenants_count')
          ->counts('tenants')
          ->label('Tenants')
          ->sortable()
          ->badge()
          ->color('success'),
        Tables\Columns\TextColumn::make('properties_count')
          ->label('Properties')
          ->getStateUsing(function (Model $record): int {
            return Property::whereHas('flats.contracts', function ($query) use ($record) {
              $query->where('agency_id', $record->id);
            })->count();
          })
          ->sortable()
          ->badge()
          ->color('warning'),
        Tables\Columns\TextColumn::make('active_contracts_count')
          ->label('Active Contracts')
          ->getStateUsing(function (Model $record): int {
            return Contract::where('agency_id', $record->id)
              ->where('status', 'active')
              ->count();
          })
          ->sortable()
          ->badge()
          ->color('info'),
        Tables\Columns\TextColumn::make('owners_count')
          ->label('Owners')
          ->getStateUsing(function (Model $record): int {
            return Owner::whereHas('property.flats.contracts', function ($query) use ($record) {
              $query->where('agency_id', $record->id);
            })->count();
          })
          ->sortable()
          ->badge()
          ->color('purple'),
        Tables\Columns\TextColumn::make('total_revenue')
          ->label('Total Revenue')
          ->getStateUsing(function (Model $record): string {
            $total = Payment::whereHas('contract', function ($query) use ($record) {
              $query->where('agency_id', $record->id);
            })->where('status', true)->sum('amount');
            return number_format($total, 2) . ' €';
          })
          ->sortable()
          ->color('success'),
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
        Tables\Filters\Filter::make('has_users')
          ->label('Has Users')
          ->query(fn(Builder $query): Builder => $query->has('members')),
        Tables\Filters\Filter::make('has_tenants')
          ->label('Has Tenants')
          ->query(fn(Builder $query): Builder => $query->has('tenants')),
        Tables\Filters\Filter::make('created_this_month')
          ->label('Created This Month')
          ->query(fn(Builder $query): Builder => $query->whereMonth('created_at', now()->month)),
      ])
      ->actions([
        Tables\Actions\ViewAction::make(),
        Tables\Actions\EditAction::make(),
        Tables\Actions\DeleteAction::make()
          ->requiresConfirmation()
          ->modalDescription('Are you sure you want to delete this agency? This action cannot be undone and will affect all related data.'),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DeleteBulkAction::make()
            ->requiresConfirmation(),
        ]),
      ])
      ->defaultSort('agencies.created_at', 'desc');
  }

  public static function infolist(Infolist $infolist): Infolist
  {
    return $infolist
      ->schema([
        Infolists\Components\Section::make('Agency Information')
          ->schema([
            Infolists\Components\TextEntry::make('name')
              ->size('lg')
              ->weight('bold'),
            Infolists\Components\TextEntry::make('slug')
              ->copyable()
              ->color('gray'),
            Infolists\Components\TextEntry::make('created_at')
              ->dateTime(),
            Infolists\Components\TextEntry::make('updated_at')
              ->dateTime(),
          ])
          ->columns(2),

        Infolists\Components\Section::make('Statistics')
          ->schema([
            Infolists\Components\TextEntry::make('members_count')
              ->label('Total Users')
              ->getStateUsing(fn(Model $record): int => $record->members()->count())
              ->badge()
              ->color('primary'),
            Infolists\Components\TextEntry::make('tenants_count')
              ->label('Total Tenants')
              ->getStateUsing(fn(Model $record): int => $record->tenants()->count())
              ->badge()
              ->color('success'),
            Infolists\Components\TextEntry::make('properties_count')
              ->label('Properties Managed')
              ->getStateUsing(function (Model $record): int {
                return Property::whereHas('flats.contracts', function ($query) use ($record) {
                  $query->where('agency_id', $record->id);
                })->count();
              })
              ->badge()
              ->color('warning'),
            Infolists\Components\TextEntry::make('active_contracts')
              ->label('Active Contracts')
              ->getStateUsing(function (Model $record): int {
                return Contract::where('agency_id', $record->id)
                  ->where('status', 'active')
                  ->count();
              })
              ->badge()
              ->color('info'),
            Infolists\Components\TextEntry::make('total_revenue')
              ->label('Total Revenue')
              ->getStateUsing(function (Model $record): string {
                $total = Payment::whereHas('contract', function ($query) use ($record) {
                  $query->where('agency_id', $record->id);
                })->where('status', true)->sum('amount');
                return number_format($total, 2) . ' €';
              })
              ->color('success'),
            Infolists\Components\TextEntry::make('pending_payments')
              ->label('Pending Payments')
              ->getStateUsing(function (Model $record): string {
                $total = Payment::whereHas('contract', function ($query) use ($record) {
                  $query->where('agency_id', $record->id);
                })->where('status', false)->sum('amount');
                return number_format($total, 2) . ' €';
              })
              ->color('danger'),
          ])
          ->columns(3),

        Infolists\Components\Section::make('Property Owners')
          ->schema([
            Infolists\Components\TextEntry::make('owners_count')
              ->label('Total Owners')
              ->getStateUsing(function (Model $record): int {
                return Owner::whereHas('property.flats.contracts', function ($query) use ($record) {
                  $query->where('agency_id', $record->id);
                })->count();
              })
              ->badge()
              ->color('purple'),

            Infolists\Components\TextEntry::make('owners_with_active_contracts')
              ->label('Owners with Active Contracts')
              ->getStateUsing(function (Model $record): int {
                return Owner::whereHas('property.flats.contracts', function ($query) use ($record) {
                  $query->where('agency_id', $record->id)
                    ->where('status', 'active');
                })->count();
              })
              ->badge()
              ->color('success'),

            Infolists\Components\TextEntry::make('total_properties_owned')
              ->label('Properties Under Management')
              ->getStateUsing(function (Model $record): int {
                return Property::whereHas('flats.contracts', function ($query) use ($record) {
                  $query->where('agency_id', $record->id);
                })->count();
              })
              ->badge()
              ->color('info'),
          ])
          ->columns(3),

        Infolists\Components\Section::make('Owner Details')
          ->schema([
            Infolists\Components\RepeatableEntry::make('owners_list')
              ->label('Property Owners')
              ->getStateUsing(function (Model $record): array {
                return Owner::whereHas('property.flats.contracts', function ($query) use ($record) {
                  $query->where('agency_id', $record->id);
                })
                  ->with(['property'])
                  ->get()
                  ->map(function ($owner) use ($record) {
                    $activeContracts = Contract::whereHas('flat', function ($query) use ($owner) {
                      $query->where('property_id', $owner->property_id);
                    })
                      ->where('agency_id', $record->id)
                      ->where('status', 'active')
                      ->count();

                    $totalRevenue = Payment::whereHas('contract.flat', function ($query) use ($owner) {
                      $query->where('property_id', $owner->property_id);
                    })
                      ->whereHas('contract', function ($query) use ($record) {
                        $query->where('agency_id', $record->id);
                      })
                      ->where('status', true)
                      ->sum('amount');

                    return [
                      'name' => $owner->name,
                      'phone' => $owner->phone,
                      'property' => $owner->property->name ?? 'N/A',
                      'property_address' => $owner->property->address ?? 'N/A',
                      'active_contracts' => $activeContracts,
                      'total_revenue' => number_format($totalRevenue, 2) . ' €',
                    ];
                  })
                  ->toArray();
              })
              ->schema([
                Infolists\Components\TextEntry::make('name')
                  ->label('Owner Name')
                  ->weight('bold'),
                Infolists\Components\TextEntry::make('phone')
                  ->label('Phone')
                  ->icon('heroicon-m-phone'),
                Infolists\Components\TextEntry::make('property')
                  ->label('Property')
                  ->color('primary'),
                Infolists\Components\TextEntry::make('property_address')
                  ->label('Address')
                  ->color('gray'),
                Infolists\Components\TextEntry::make('active_contracts')
                  ->label('Active Contracts')
                  ->badge()
                  ->color('success'),
                Infolists\Components\TextEntry::make('total_revenue')
                  ->label('Total Revenue')
                  ->color('success')
                  ->weight('bold'),
              ])
              ->columns(3)
              ->columnSpanFull(),
          ])
          ->collapsible(),
      ]);
  }

  public static function getRelations(): array
  {
    return [
      RelationManagers\MembersRelationManager::class,
      RelationManagers\TenantsRelationManager::class,
    ];
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListAgencies::route('/'),
      'create' => Pages\CreateAgency::route('/create'),
      'view' => Pages\ViewAgency::route('/{record}'),
      'edit' => Pages\EditAgency::route('/{record}/edit'),
    ];
  }

  // Override the query to show all agencies (no tenant scoping)
  public static function getEloquentQuery(): Builder
  {
    return parent::getEloquentQuery()->withoutGlobalScopes();
  }
}
