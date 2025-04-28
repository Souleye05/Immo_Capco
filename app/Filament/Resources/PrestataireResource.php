<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrestataireResource\Pages;
use App\Filament\Resources\PrestataireResource\RelationManagers;
use App\Models\Prestataire;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PrestataireResource extends Resource
{
    protected static ?string $model = Prestataire::class;

    protected static ?string $navigationGroup = 'Gestion';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                Forms\Components\TextInput::make('nom')
                    ->required(),
                Forms\Components\TextInput::make('profession')
                    ->required(),
                Forms\Components\TextInput::make('phone')
                    ->tel(),
                Forms\Components\TextInput::make('adresse'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                Tables\Columns\TextColumn::make('nom')
                    ->searchable(),
                Tables\Columns\TextColumn::make('profession'),
                Tables\Columns\TextColumn::make('phone'),
                Tables\Columns\TextColumn::make('adresse')
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y')
                    ->label('Créé le'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListPrestataires::route('/'),
            'create' => Pages\CreatePrestataire::route('/create'),
            'edit' => Pages\EditPrestataire::route('/{record}/edit'),
        ];
    }
}
