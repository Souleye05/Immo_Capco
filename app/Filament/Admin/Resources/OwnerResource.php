<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\OwnerResource\Pages;
use App\Filament\Admin\Resources\OwnerResource\RelationManagers;
use App\Models\Owner;
use App\Traits\HasAgencyPermissions;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Property;
use Illuminate\Database\Eloquent\Builder;

class OwnerResource extends Resource
{
    use HasAgencyPermissions;
    protected static ?string $model = Owner::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationGroup = 'Client';

    // Désactiver le tenant scoping automatique
    protected static ?string $tenantOwnershipRelationshipName = null;

    // Désactiver complètement le tenant scoping
    public static function isScopedToTenant(): bool
    {
        return false;
    }

    public static ?string $label = 'Proprétaire';

    // Permissions spécifiques pour la gestion des propriétaires
    protected static function getViewPermission(): string
    {
        return 'view_owners';
    }

    protected static function getCreatePermission(): string
    {
        return 'create_owners';
    }

    protected static function getEditPermission(): string
    {
        return 'edit_owners';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete_owners';
    }

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
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Un compte utilisateur sera automatiquement créé ou associé avec cette adresse email pour permettre l\'accès au portail propriétaire.')
                    ->validationMessages([
                        'required' => 'L\'adresse email est obligatoire.',
                        'email' => 'Veuillez saisir une adresse email valide.',
                        'unique' => 'Cette adresse email est déjà utilisée par un autre propriétaire.',
                        'max' => 'L\'adresse email ne peut pas dépasser 255 caractères.',
                    ]),
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
                Tables\Columns\TextColumn::make('properties_count')
                    ->label('Nombre de propriétés')
                    ->counts('properties')
                    ->sortable(),
                Tables\Columns\TextColumn::make('properties.name')
                    ->label('Propriétés')
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->expandableLimitedList(),
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

    // Scoping personnalisé pour filtrer par agence via properties
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if ($tenant = \Filament\Facades\Filament::getTenant()) {
            // Debug: Log pour voir si cette méthode est appelée
            \Log::info('OwnerResource getEloquentQuery called with tenant: ' . $tenant->name . ' (ID: ' . $tenant->id . ')');

            // Inclure les owners qui ont des propriétés dans cette agence
            // OU les owners qui n'ont pas encore de propriétés (pour permettre l'édition après création)
            $query->where(function ($subQuery) use ($tenant) {
                $subQuery->whereHas('properties', function ($propertyQuery) use ($tenant) {
                    $propertyQuery->where('agency_id', $tenant->id);
                })->orWhereDoesntHave('properties');
            });
        } else {
            \Log::info('OwnerResource getEloquentQuery called but no tenant found');
        }

        return $query;
    }
}
