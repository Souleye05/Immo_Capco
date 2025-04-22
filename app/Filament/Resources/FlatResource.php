<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FlatResource\Pages;
use App\Filament\Resources\FlatResource\RelationManagers;
use App\Models\Flat;
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
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FlatResource extends Resource
{
    protected static ?string $model = Flat::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';
    protected static ?string $navigationGroup = 'Gestion';

    public static ?string $label = 'appartement';

    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('property_id')
                    ->label('Propriété')
                    ->options(Property::all()->pluck('name', 'id'))
                    ->reactive()
                    ->afterStateUpdated(fn($state, callable $set) => $set('property_commission_value', Property::find($state)?->commission_value ?? 0) and $set('property_commission_unit', Property::find($state)?->commission_unit ?? 0)),
                Select::make('type')
                    ->options([
                        'chambre' => 'Chambre',
                        'chambre + SDB' => 'Chambre + SDB',
                        'studio' => 'Studio',
                        'f1' => 'F1',
                        'f2' => 'F2',
                        'f3' => 'F3',
                        'f4' => 'F4',
                        'f5' => 'F5',
                        'f6+' => 'F6+',
                    ])
                    ->required(),
                Select::make('tenant_id')
                    ->label('Locataire')
                    ->options(Tenant::all()->pluck('name', 'id')->toArray())
                    ->searchable(),
                TextInput::make('reference')
                    ->label("Référence de l'appartement"),
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('property.name')
                    ->label('Propriété')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Locataire')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('loyer')
                    ->label('Montant du loyer')
                    ->money('XOF')
                    ->sortable(),
                Tables\Columns\TextColumn::make('caution')
                    ->label('Montant de la caution')
                    ->money('XOF')
                    ->sortable(),
                Tables\Columns\TextColumn::make('property_commission_value')
                    ->label('Commission')
                    ->sortable(),
                Tables\Columns\TextColumn::make('property_commission_unit')
                    ->label('Unité')
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
}
