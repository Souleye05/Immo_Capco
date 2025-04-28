<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategorieDepenseResource\Pages;
use App\Filament\Resources\CategorieDepenseResource\RelationManagers;
use App\Models\CategorieDepense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CategorieDepenseResource extends Resource
{
    protected static ?string $model = CategorieDepense::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Paiement';
    protected static ?string $navigationLabel = 'Catégories de dépenses';

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            //
            Forms\Components\TextInput::make('categorie')
                ->label('Categorie'),   
            Forms\Components\Textarea::make('description')
                ->label('Description')
                ->rows(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('categorie')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('description')
                ->limit(50)
                ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d/m/Y')->label('Créé le'),
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
            'index' => Pages\ListCategorieDepenses::route('/'),
            'create' => Pages\CreateCategorieDepense::route('/create'),
            'edit' => Pages\EditCategorieDepense::route('/{record}/edit'),
        ];
    }
}
