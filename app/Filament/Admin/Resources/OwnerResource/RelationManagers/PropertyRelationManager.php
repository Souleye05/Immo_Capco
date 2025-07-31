<?php

namespace App\Filament\Admin\Resources\OwnerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PropertyRelationManager extends RelationManager
{
    protected static string $relationship = 'Property';
    protected static ?string $title = 'Propriétés';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom de la propriété')
                    ->searchable()
                    ->sortable()
                    ->alignCenter(), // Centrer la colonne

                Tables\Columns\TextColumn::make('type')
                    ->label('Type de propriété')
                    ->sortable()
                    ->alignCenter(), // Centrer la colonne

                Tables\Columns\TextColumn::make('address')
                    ->label('Adresse')
                    ->searchable()
                    ->alignCenter(), // Centrer la colonne

                Tables\Columns\TextColumn::make('number_flat')
                    ->label('Nombre d\'appartements')
                    ->sortable()
                    ->alignCenter(), // Centrer la colonne

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d F Y') // Formater la date au format "07 mai 2025"
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->translatedFormat('d F Y')) // Traduire le mois
                    ->alignCenter(), // Centrer la colonne
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
