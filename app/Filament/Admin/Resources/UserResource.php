<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Tables\Table;
use App\Traits\HasAgencyPermissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserResource extends Resource
{
    use HasAgencyPermissions;
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationGroup = 'Utilisateurs';

    // Désactiver le tenant scoping automatique
    protected static ?string $tenantOwnershipRelationshipName = null;

    // Désactiver complètement le tenant scoping
    public static function isScopedToTenant(): bool
    {
        return false;
    }

    public static ?string $label = 'agents';

    public static function form(Form $form): Form
    {
        return $form

            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Adresse Mail')
                    ->required()
                    ->maxLength(255),
                TextInput::make('password')
                    ->password()
                    ->label('Mot de passe')
                    ->minLength(8)
                    ->same('passwordConfirmation')
                    ->dehydrated(fn($state) => filled($state))
                    ->dehydrateStateUsing(fn($state) => Hash::make($state)),
                TextInput::make('passwordConfirmation')
                    ->password()
                    ->label('Confirmer mot de passe')
                    ->minLength(8)
                    ->dehydrated(false),
                Forms\Components\Select::make('roles')
                    ->relationship('roles', 'name', function ($query) {
                        // Exclure le rôle super-admin pour les agency-owners
                        $user = auth()->user();
                        if ($user && !$user->hasRole('super-admin')) {
                            $query->where('name', '!=', 'super-admin');
                        }
                    })
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->label('Rôles système'),

                Forms\Components\Select::make('agency_roles')
                    ->label('Rôles d\'équipe')
                    ->multiple()
                    ->options(function () {
                        if ($tenant = \Filament\Facades\Filament::getTenant()) {
                            return \App\Models\AgencyRole::where('agency_id', $tenant->id)
                                ->where('is_active', true)
                                ->pluck('name', 'id');
                        }
                        return [];
                    })
                    ->searchable()
                    ->helperText('Rôles personnalisés créés pour cette agence'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Adresse Mail')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label('Email vérifié le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    // Permissions spécifiques pour la gestion des utilisateurs
    protected static function getViewPermission(): string
    {
        return 'view_team_users';
    }

    protected static function getCreatePermission(): string
    {
        return 'view_team_users'; // Même permission pour voir et créer
    }

    protected static function getEditPermission(): string
    {
        return 'manage_agency_roles'; // Seuls ceux qui peuvent gérer les rôles peuvent modifier les utilisateurs
    }

    protected static function getDeletePermission(): string
    {
        return 'manage_agency_roles';
    }

    // Scoping personnalisé pour filtrer par agence via la relation many-to-many
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if ($tenant = \Filament\Facades\Filament::getTenant()) {
            // Debug: Log pour voir si cette méthode est appelée
            Log::info("UserResource getEloquentQuery called with tenant: {$tenant->name} (ID: {$tenant->id})");

            $query->whereHas('agencys', function ($subQuery) use ($tenant) {
                $subQuery->where('agency_id', $tenant->id);
            });
        } else {
            Log::info('UserResource getEloquentQuery called but no tenant found');
        }

        return $query;
    }
}
