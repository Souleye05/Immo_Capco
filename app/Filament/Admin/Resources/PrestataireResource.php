<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PrestataireResource\Pages;
use App\Filament\Admin\Resources\PrestataireResource\RelationManagers;
use App\Models\Prestataire;
use App\Traits\HasAgencyPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PrestataireResource extends Resource
{
    use HasAgencyPermissions;
    protected static ?string $model = Prestataire::class;

    protected static ?string $navigationGroup = 'Gestion';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    // Désactiver le tenant scoping automatique
    protected static ?string $tenantOwnershipRelationshipName = null;

    // Désactiver complètement le tenant scoping
    public static function isScopedToTenant(): bool
    {
        return false;
    }

    // Permissions spécifiques pour la gestion des prestataires
    protected static function getViewPermission(): string
    {
        return 'view_expenses'; // Les prestataires sont liés aux dépenses
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
                    ->searchable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('profession')
                    ->searchable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('adresse')
                    ->searchable()
                    ->limit(50)
                    ->alignCenter(),
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

    // Scoping personnalisé - les prestataires sont globaux (pas de filtrage par agence)
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        // Debug: Log pour voir si cette méthode est appelée
        if ($tenant = \Filament\Facades\Filament::getTenant()) {
            \Log::info('PrestataireResource getEloquentQuery called with tenant: ' . $tenant->name . ' (ID: ' . $tenant->id . ')');
        } else {
            \Log::info('PrestataireResource getEloquentQuery called but no tenant found');
        }

        // Les prestataires sont globaux - pas de filtrage par agence
        // Tous les prestataires sont visibles pour toutes les agences
        return $query;
    }
}
