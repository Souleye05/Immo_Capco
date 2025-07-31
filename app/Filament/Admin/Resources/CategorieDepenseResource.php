<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CategorieDepenseResource\Pages;
use App\Filament\Admin\Resources\CategorieDepenseResource\RelationManagers;
use App\Models\CategorieDepense;
use App\Traits\HasAgencyPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategorieDepenseResource extends Resource
{
    use HasAgencyPermissions;
    protected static ?string $model = CategorieDepense::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Paiement';
    protected static ?string $navigationLabel = 'Catégories de dépenses';

    // Désactiver le tenant scoping automatique
    protected static ?string $tenantOwnershipRelationshipName = null;

    // Désactiver complètement le tenant scoping
    public static function isScopedToTenant(): bool
    {
        return false;
    }

    // Permissions spécifiques pour la gestion des catégories de dépenses
    protected static function getViewPermission(): string
    {
        return 'view_expenses'; // Lié aux dépenses
    }

    protected static function getCreatePermission(): string
    {
        return 'create_expenses';
    }

    protected static function getEditPermission(): string
    {
        return 'edit_expenses';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete_expenses';
    }

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
                    ->alignCenter()
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->alignCenter()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->alignCenter()
                    ->label('Créé le'),
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

    // Scoping personnalisé - les catégories de dépenses sont globales (pas de filtrage par agence)
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Debug: Log pour voir si cette méthode est appelée
        if ($tenant = \Filament\Facades\Filament::getTenant()) {
            \Log::info('CategorieDepenseResource getEloquentQuery called with tenant: ' . $tenant->name . ' (ID: ' . $tenant->id . ')');
        } else {
            \Log::info('CategorieDepenseResource getEloquentQuery called but no tenant found');
        }

        // Les catégories de dépenses sont globales - pas de filtrage par agence
        // Toutes les catégories sont visibles pour toutes les agences
        return $query;
    }
}
