<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AgencyRoleResource\Pages;
use App\Models\AgencyRole;
use App\Traits\HasAgencyPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Model;

class AgencyRoleResource extends Resource
{
    use HasAgencyPermissions;
    protected static ?string $model = AgencyRole::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Rôles d\'équipe';

    protected static ?string $modelLabel = 'Rôle d\'équipe';

    protected static ?string $pluralModelLabel = 'Rôles d\'équipe';

    protected static ?string $navigationGroup = 'Gestion d\'équipe';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du rôle')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom du rôle')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: Manager Commercial, Assistant Comptable')
                            ->helperText('Nom descriptif du rôle dans votre équipe'),

                        Forms\Components\TextInput::make('slug')
                            ->label('Identifiant')
                            ->maxLength(255)
                            ->helperText('Généré automatiquement si vide')
                            ->placeholder('manager-commercial'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->placeholder('Décrivez les responsabilités de ce rôle...')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Rôle actif')
                            ->default(true)
                            ->helperText('Les rôles inactifs ne peuvent pas être assignés'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Permissions')
                    ->schema(
                        collect(\App\Services\AgencyPermissionService::getAllPermissions())
                            ->map(function ($permissions, $group) {
                                return Forms\Components\Fieldset::make(ucfirst(__($group)))
                                    ->schema([
                                        Forms\Components\CheckboxList::make("permissions_group_{$group}")
                                            ->label(false)
                                            ->options($permissions)
                                            ->columns(2),
                                    ])
                                    ->columns(1);
                            })->values()->all()
                    )
                    ->columnSpanFull()
                    ->afterStateHydrated(function (Get $get, Set $set) {
                        $existing = $get('permissions') ?? [];

                        foreach (\App\Services\AgencyPermissionService::getAllPermissions() as $group => $permissions) {
                            $set("permissions_group_{$group}", array_values(array_intersect(array_keys($permissions), $existing)));
                        }
                    }),


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom du rôle')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Utilisateurs')
                    ->counts('users')
                    ->badge()
                    ->color('info'),

                // Tables\Columns\TextColumn::make('permissions')
                //     ->label('Permissions')
                //     ->formatStateUsing(fn($state): string => is_array($state) ? count($state) . ' permissions' : '0 permissions')
                //     ->badge()
                //     ->color('success'),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->getStateUsing(function (Model $record) {
                        return is_array($record->permissions) ? count($record->permissions) : 0;
                    })
                    ->formatStateUsing(fn($state) => "{$state} permission" . ($state > 1 ? 's' : ''))
                    ->badge()
                    ->color(fn($state) => $state === 0 ? 'gray' : 'success')
                    ->sortable(),
                                                                                                                                                                                                                                                                                                                                                        


                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Créé par')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Statut')
                    ->placeholder('Tous les rôles')
                    ->trueLabel('Rôles actifs')
                    ->falseLabel('Rôles inactifs'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Voir'),
                Tables\Actions\EditAction::make()
                    ->label('Modifier'),
                Tables\Actions\DeleteAction::make()
                    ->label('Supprimer')
                    ->requiresConfirmation()
                    ->modalDescription('Êtes-vous sûr de vouloir supprimer ce rôle ? Cette action supprimera également toutes les assignations de ce rôle.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Supprimer sélectionnés')
                        ->requiresConfirmation(),
                ]),
            ])
            ->emptyStateHeading('Aucun rôle d\'équipe')
            ->emptyStateDescription('Créez votre premier rôle personnalisé pour organiser votre équipe.')
            ->emptyStateIcon('heroicon-o-user-group');
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
            'index' => Pages\ListAgencyRoles::route('/'),
            'create' => Pages\CreateAgencyRole::route('/create'),
            'edit' => Pages\EditAgencyRole::route('/{record}/edit'),
        ];
    }

    // Permissions spécifiques pour la gestion des rôles d'agence
    protected static function getViewPermission(): string
    {
        return 'manage_agency_roles';
    }

    protected static function getCreatePermission(): string
    {
        return 'manage_agency_roles';
    }

    protected static function getEditPermission(): string
    {
        return 'manage_agency_roles';
    }

    protected static function getDeletePermission(): string
    {
        return 'manage_agency_roles';
    }

    // Sécurité : Seuls les agency-owners et super-admins peuvent accéder à cette ressource
    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Les super-admins et agency-owners ont accès complet
        if ($user->hasRole('super-admin') || $user->hasRole('agency-owner')) {
            return true;
        }

        // Sinon, utiliser notre système de permissions personnalisé
        return static::hasAgencyPermission('manage_agency_roles');
    }

    // Scoping : Filtrer les rôles par agence courante
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('agency_id', Filament::getTenant()?->id)
            ->with(['creator', 'users']);
    }
}
