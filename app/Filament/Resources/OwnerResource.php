<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OwnerResource\Pages;
use App\Filament\Resources\OwnerResource\RelationManagers;
use App\Models\Owner;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Property;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OwnerResource extends Resource
{
    protected static ?string $model = Owner::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    
    protected static ?string $navigationGroup = 'Client';

    public static ?string $label = 'Proprétaire';

    public static function form(Form $form): Form
{
    return $form
        ->schema([
            TextInput::make('name')
                ->label('Nom & Prénoms')
                ->required()
                ->maxLength(255),
            TextInput::make('phone')
                ->tel()
                ->required()
                ->maxLength(255),
            Select::make('property_id')
                ->label('Propriété')
                ->options(Property::all()->pluck('name', 'id'))
                ->searchable()
                ->createOptionForm([
                    Select::make('type')
                        ->label('Type de propriété')
                        ->options([
                            'Immeuble' => 'Immeuble',
                            'Villa' => 'Villa',
                            'Commerce' => 'Commerce'
                        ])
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
                    return Property::create([
                        'type' => $data['type'],
                        'name' => $data['name'],
                        'address' => $data['address'],
                        'commission_value' => $data['commission_value'],
                        'commission_unit' => $data['commission_unit'],
                        'number_flat' => $data['number_flat'],
                    ])->id;
                })
                ->createOptionAction(function ($action) {
                    return $action
                        ->modalHeading('Créer une nouvelle propriété')
                        ->modalButton('Créer propriété')
                        ->modalWidth('lg');
                })
        ]);
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom & Prénoms')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('property.name')
                    ->label('Propriété')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Mis à jour le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
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
            RelationManagers\RemittanceRelationManager::class,
            RelationManagers\PropertyRelationManager::class, // Affiche les propriétés
            // RelationManagers\RemittancePartialRelationManager::class,
             // Affiche les remittance partials
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOwners::route('/'),
            'create' => Pages\CreateOwner::route('/create'),
            'edit' => Pages\EditOwner::route('/{record}/edit'),
        ];
    }
}
