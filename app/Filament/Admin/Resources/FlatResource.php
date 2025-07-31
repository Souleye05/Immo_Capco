<?php

namespace App\Filament\Admin\Resources;

use App\Enums\PropertyType;
use App\Filament\Admin\Resources\FlatResource\Pages;
use App\Filament\Admin\Resources\FlatResource\RelationManagers;
use App\Filament\Admin\Resources\FlatResource\Widgets\FlatStatsWidget;
use App\Enums\FlatType;
use App\Models\Flat;
use App\Traits\HasAgencyPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use App\Models\Tenant;
use App\Models\Property;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class FlatResource extends Resource
{
    use HasAgencyPermissions;
    protected static ?string $model = Flat::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';
    protected static ?string $navigationGroup = 'Gestion';

    // Désactiver le tenant scoping automatique
    protected static ?string $tenantOwnershipRelationshipName = null;

    // Désactiver complètement le tenant scoping
    public static function isScopedToTenant(): bool
    {
        return false;
    }

    public static ?string $label = 'appartement';

    // Permissions spécifiques pour la gestion des appartements
    protected static function getViewPermission(): string
    {
        return 'view_properties'; // Les flats sont liés aux propriétés
    }

    protected static function getCreatePermission(): string
    {
        return 'create_properties';
    }

    protected static function getEditPermission(): string
    {
        return 'edit_properties';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete_properties';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('property_id')
                    ->label('Propriété')
                    ->options(Property::all()->pluck('name', 'id'))
                    ->searchable()
                    ->reactive()
                    ->createOptionForm([
                        Select::make('type')
                            ->label('Type de propriété')
                            ->options(PropertyType::getOptions())
                            ->native(false)
                            ->required(),
                        TextInput::make('name')
                            ->label('Nom')
                            ->required(),

                        TextInput::make('address')
                            ->label('Adresse')
                            ->required(),
                        TextInput::make('commission_value')
                            ->label('Commission sur la propriété')
                            ->numeric(),
                        Select::make('commission_unit')
                            ->label('Unité')
                            ->options([
                                '%' => '%',
                                'F CFA' => 'F CFA'
                            ])
                            ->required(),
                        TextInput::make('number_flat')
                            ->label("Nombre d'appartement dans la propriété")
                            ->numeric()
                            ->minValue(1),
                    ])
                    ->createOptionUsing(function (array $data) {
                        return Property::create($data)->id;
                    })
                    ->createOptionAction(function ($action) {
                        return $action
                            ->modalHeading('Créer une nouvelle propriété')
                            ->modalButton('Créer propriété')
                            ->modalWidth('lg');
                    })
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $property = Property::find($state);
                            if ($property) {
                                $set('property_commission_value', $property->commission_value);
                                $set('property_commission_unit', $property->commission_unit);
                            }
                        }
                    })
                    ->helperText(function ($state) {
                        if ($state) {
                            $property = Property::find($state);
                            if ($property && $property->number_flat) {
                                $existingCount = Flat::where('property_id', $state)->count();
                                $remaining = $property->number_flat - $existingCount;
                                return "Appartements disponibles : {$remaining}/{$property->number_flat}";
                            }
                        }
                        return null;
                    })
                    ->rules([
                        function () {
                            return function (string $attribute, $value, \Closure $fail) {
                                if ($value) {
                                    $property = Property::find($value);
                                    if ($property && $property->number_flat) {
                                        // Compter le nombre de flats existants pour cette propriété
                                        $existingFlatsCount = Flat::where('property_id', $value)->count();

                                        if ($existingFlatsCount >= $property->number_flat) {
                                            $fail("Cette propriété a atteint sa capacité maximale de {$property->number_flat} appartement(s). Actuellement {$existingFlatsCount} appartement(s) sont déjà créés.");
                                        }
                                    }
                                }
                            };
                        }
                    ]),

                Select::make('type')
                    ->options(FlatType::options())
                    ->required(),
                Forms\Components\TextInput::make('designation')
                    ->label('Désignation de l\'appartement')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('level')
                    ->label('Niveau')
                    ->placeholder('Ex: Rez-de-chaussée, 1er étage à gauche, 2ème étage...')
                    ->required(),
                // ->numeric()
                // ->minValue(0),


                TextInput::make('reference')
                    ->label("Référence de l'appartement")
                    ->default('FLAT-' . random_int(100000, 999999))
                    ->disabled()
                    ->dehydrated(true)
                    ->required(),

                TextInput::make('loyer')
                    ->label('Montant du loyer')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->reactive(),
                // ->afterStateUpdated(fn(string $context, $state, callable $set) => $context === 'create' ? $set('caution', Str::slug($state)) : null),
                TextInput::make('caution')
                    ->label('Montant de la caution')
                    ->numeric()
                    ->required()
                    ->minValue(1),
                TextInput::make('property_commission_value')
                    ->label('Commission')
                    ->numeric()
                    ->required(),
                Select::make('property_commission_unit')
                    ->label('Unité')
                    ->options([
                        '%' => '%',
                        'F CFA' => 'F CFA'
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label("Référence de l'appartement")
                    ->searchable()
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('property.name')
                    ->label('Propriété')
                    ->searchable()
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->searchable()
                    ->alignCenter()
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn($state) => $state->label()),
                Tables\Columns\TextColumn::make('designation')
                    ->label('Désignation')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('level')
                    ->label('Niveau')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),
                // ->suffix(fn ($state)),


                Tables\Columns\TextColumn::make('loyer')
                    ->label('Montant du loyer')
                    ->alignCenter()
                    // ->money('XOF')
                    ->suffix('F CFA')

                    ->sortable(),
                Tables\Columns\TextColumn::make('caution')
                    ->label('Montant de la caution')
                    ->alignCenter()
                    // ->money('XOF')
                    ->suffix('F CFA')
                    ->sortable(),
                Tables\Columns\TextColumn::make('is_occupied')
                    ->label('Statut')
                    ->badge()
                    ->getStateUsing(fn($record) => $record->is_occupied)
                    ->formatStateUsing(fn($state) => $state ? 'Occupé' : 'Libre')
                    ->colors([
                        'success' => false, // Libre = vert
                        'danger' => true,   // Occupé = rouge
                    ]),

                Tables\Columns\TextColumn::make('current_tenant_name')
                    ->label('Locataire actuel')
                    ->getStateUsing(function ($record) {
                        return $record->current_tenant?->name ?? 'Aucun';
                    })
                    ->placeholder('Aucun')
                    ->searchable(['tenants.name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('occupancy_date')
                    ->label('Occupé depuis')
                    ->getStateUsing(fn($record) => $record->occupancy_date)
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),

                // groupe les colonnes commission_value et property_commission_unit
                Tables\Columns\TextColumn::make('property_commission_value')
                    ->label('Commission')
                    ->alignCenter()

                    ->suffix(fn(Flat $record) => ' ' . $record->property_commission_unit)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->alignCenter()

                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Mis à jour le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->alignCenter()

                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('property_id')
                    ->label('Propriété')
                    ->relationship('property', 'name'),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options(Flat::options()),

                Tables\Filters\Filter::make('occupied')
                    ->label('Appartements occupés')
                    ->query(fn($query) => $query->occupied()),

                Tables\Filters\Filter::make('available')
                    ->label('Appartements libres')
                    ->query(fn($query) => $query->available()),

                Tables\Filters\Filter::make('has_tenant')
                    ->label('Avec locataire attribué')
                    ->query(fn($query) => $query->whereNotNull('tenant_id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFlats::route('/'),
            'create' => Pages\CreateFlat::route('/create'),
            'edit' => Pages\EditFlat::route('/{record}/edit'),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informations de l\'appartement')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('property.name')
                                    ->label('Propriété'),
                                Infolists\Components\TextEntry::make('reference')
                                    ->label('Référence'),
                                Infolists\Components\TextEntry::make('designation')
                                    ->label('Désignation'),
                                Infolists\Components\TextEntry::make('type')
                                    ->label('Type')
                                    ->formatStateUsing(fn($state) => $state->label()),
                                Infolists\Components\TextEntry::make('level')
                                    ->label('Niveau'),
                                Infolists\Components\TextEntry::make('monthly_rent')
                                    ->label('Loyer mensuel')
                                    ->money('XOF'),
                                Infolists\Components\TextEntry::make('caution')
                                    ->label('Dépôt de garantie')
                                    ->money('XOF'),
                            ]),
                    ]),

                // 🔹 SECTION: Informations d'occupation
                Infolists\Components\Section::make('Occupation actuelle')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Statut')
                                    ->getStateUsing(fn($record) => $record->status)
                                    ->badge()
                                    ->color(fn($state) => $state === 'Occupé' ? 'danger' : 'success'),

                                Infolists\Components\TextEntry::make('current_tenant.name')
                                    ->label('Locataire actuel')
                                    ->getStateUsing(fn($record) => $record->current_tenant?->name)
                                    ->placeholder('Aucun locataire')
                                    ->url(fn($record) => $record->current_tenant ?
                                        TenantResource::getUrl('view', ['record' => $record->current_tenant]) : null),

                                Infolists\Components\TextEntry::make('occupancy_date')
                                    ->label('Date d\'occupation')
                                    ->getStateUsing(fn($record) => $record->occupancy_date)
                                    ->date('d/m/Y')
                                    ->placeholder('—'),

                                Infolists\Components\TextEntry::make('current_tenant.phone')
                                    ->label('Téléphone du locataire')
                                    ->getStateUsing(fn($record) => $record->current_tenant?->phone)
                                    ->placeholder('—')
                                    ->url(fn($state) => $state ? "tel:{$state}" : null),
                            ]),
                    ]),

                // 🔹 SECTION: Contrats
                Infolists\Components\Section::make('Historique des contrats')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('contracts')
                            ->label('')
                            ->schema([
                                Infolists\Components\Grid::make(3)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('tenant.name')
                                            ->label('Locataire'),
                                        Infolists\Components\TextEntry::make('start_date')
                                            ->label('Début')
                                            ->date('d/m/Y'),
                                        Infolists\Components\TextEntry::make('end_date')
                                            ->label('Fin')
                                            ->date('d/m/Y'),
                                        Infolists\Components\TextEntry::make('status')
                                            ->label('Statut')
                                            ->badge(),
                                    ]),
                            ])
                            ->contained(false),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // 🔹 SECTION: Commission
                Infolists\Components\Section::make('Commission propriété')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('property_commission_value')
                                    ->label('Valeur de la commission'),
                                Infolists\Components\TextEntry::make('property_commission_unit')
                                    ->label('Unité de la commission'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    // Scoping personnalisé pour filtrer par agence via property
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        if ($tenant = \Filament\Facades\Filament::getTenant()) {
            // Debug: Log pour voir si cette méthode est appelée
            \Log::info('FlatResource getEloquentQuery called with tenant: ' . $tenant->name . ' (ID: ' . $tenant->id . ')');

            $query->whereHas('property', function ($subQuery) use ($tenant) {
                $subQuery->where('agency_id', $tenant->id);
            });
        } else {
            \Log::info('FlatResource getEloquentQuery called but no tenant found');
        }

        return $query;
    }
}
