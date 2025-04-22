<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RemittanceResource\Pages;
use App\Filament\Resources\RemittanceResource\RelationManagers;
use App\Models\Remittance;
use Filament\Forms;
use App\Models\Owner;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\DatePicker;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RemittanceResource extends Resource
{
    protected static ?string $model = Remittance::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Paiement';

    public static ?string $label = 'Versement';

    public static function form(Form $form): Form
{
    return $form
        ->schema([
            Select::make('owner_id')
                ->label('Propriétaire')
                ->options(Owner::all()->pluck('name', 'id'))
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    $owner = Owner::find($state);
                    if ($owner) {
                        $set('property_id', $owner->property_id ?? null); // À adapter selon ton modèle
                    } else {
                        $set('property_id', null);
                    }
                })
                ->required(),

            TextInput::make('property_id')
                ->label('ID de la propriété')
                ->disabled()
                ->dehydrated(true)
                ->required(),

                TextInput::make('amount')
                ->label('Montant')
                ->numeric()
                ->suffix('FCFA')
                ->required(),            

            DatePicker::make('remittance_date')
                ->label('Date de versement')
                ->required(),

            Select::make('mode_remit')
                ->label('Mode de versement')
                ->searchable()
                ->options([
                    'OM' => 'OM',
                    'Wave' => 'Wave',
                    'Free Money' => 'Free Money',
                    'Chèque' => 'Chèque',
                    'Virement' => 'Virement',
                    'Espèces' => 'Espèces',
                ])
                ->required(),
        ]);
}


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Propriétaire')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('property_id')
                    ->label('Propriété ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                ->label('Montant')
                ->numeric()
                ->suffix('FCFA'),
                Tables\Columns\TextColumn::make('remittance_date')
                    ->label('Date de versement')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('mode_remit')
                    ->label('Mode de versement')
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
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRemittances::route('/'),
            'create' => Pages\CreateRemittance::route('/create'),
            'edit' => Pages\EditRemittance::route('/{record}/edit'),
        ];
    }
}
