<?php

namespace App\Filament\Resources\PropertyResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FlatRelationManager extends RelationManager
{
    protected static string $relationship = 'flats';

    protected static ?string $title = 'Appartements';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('reference')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->sortable()
                    ->alignCenter(), // Centrer la colonne
                Tables\Columns\TextColumn::make('type')
                    ->label('Propriété (Immeuble)')
                    ->alignCenter() // Centrer la colonne
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_tenant_name')
                    ->label('Locataire actuel')
                    ->getStateUsing(function ($record) {
                        return $record->current_tenant?->name ?? 'Aucun';
                    })
                    ->placeholder('Aucun')
                    ->searchable(['tenants.name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('loyer')
                    ->label('Loyer')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ') . ' FCFA')
                    ->alignCenter() // Centrer la colonne
                    ->sortable(),
                Tables\Columns\TextColumn::make('caution')
                    ->label('Caution')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ') . ' FCFA')
                    ->alignCenter() // Centrer la colonne
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date de création')
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->translatedFormat('d F Y')) // Traduire le mois
                    ->alignCenter() // Centrer la colonne
                    ->sortable(),
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
