<?php

namespace App\Filament\Resources;

use App\Enums\ProspectObjetEnum;
use App\Enums\ProspectTypeEnum;
use App\Filament\Resources\ProspectResource\Pages;
use App\Models\Prospect;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;

class ProspectResource extends Resource
{
    protected static ?string $model = Prospect::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationGroup = 'Client';

    public static ?string $label = 'prospects';
    protected static ?string $modelLabel = 'Prospect';
    protected static ?string $pluralModelLabel = 'Prospects';


//    public static function form(Form $form): Form
//     {
//         return $form->schema([
//             Forms\Components\Section::make('Informations de contact')
//                 ->description('Informations de base du prospect')
//                 ->schema([
//                     Forms\Components\TextInput::make('name')
//                         ->label('Nom')
//                         ->required()
//                         ->maxLength(255)
//                         ->columnSpan(1),

//                     Forms\Components\TextInput::make('email')
//                         ->label('Adresse email')
//                         ->email()
//                         ->maxLength(255)
//                         ->columnSpan(1),

//                     Forms\Components\TextInput::make('phone')
//                         ->label('Téléphone')
//                         ->tel()
//                         ->maxLength(20)
//                         ->columnSpan(1),
//                 ])
//                 ->columns(2)
//                 ->collapsible(),

//             Forms\Components\Section::make('Préférences et critères')
//                 ->description('Définissez les préférences du prospect')
//                 ->schema([
//                     Forms\Components\CheckboxList::make('objet')
//                         ->label('Objet de la recherche')
//                         ->options(ProspectObjetEnum::options())
//                         ->columns(2)
//                         ->gridDirection('row')
//                         ->helperText('Sélectionnez un ou plusieurs objets'),

//                     Forms\Components\CheckboxList::make('type')
//                         ->label('Type de bien')
//                         ->options(ProspectTypeEnum::options())
//                         ->columns(3)
//                         ->gridDirection('row')
//                         ->helperText('Sélectionnez un ou plusieurs types'),

//                     Forms\Components\TextInput::make('budget')
//                         ->label('Budget')
//                         ->placeholder('Ex: 500€ - 800€/mois, 150 000€ - 200 000€')
//                         ->maxLength(255)
//                         ->helperText('Indiquez la fourchette budgétaire souhaitée'),

//                     Forms\Components\TextInput::make('secteur_localisation')
//                         ->label('Secteur ou localisation')
//                         ->placeholder('Ex: Liberté 5, Sacré Coeur 3, Centre-ville...')
//                         ->maxLength(255)
//                         ->helperText('Précisez le secteur ou la localisation préférée'),
//                 ])
//                 ->columns(1)
//                 ->collapsible(),
//         ]);
//     }

public static function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\Section::make('Informations de contact')
            ->description('Informations de base du prospect')
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(1),

                Forms\Components\TextInput::make('email')
                    ->label('Adresse email')
                    ->email()
                    ->maxLength(255)
                    ->columnSpan(1),

                Forms\Components\TextInput::make('phone')
                    ->label('Téléphone')
                    ->tel()
                    ->maxLength(20)
                    ->columnSpan(1),
            ])
            ->columns(2)
            ->collapsible(),

        Forms\Components\Section::make('Préférences et critères')
            ->description('Définissez les préférences du prospect')
            ->schema([
                Forms\Components\CheckboxList::make('objet')
                    ->label('Objet de la recherche')
                    ->options(ProspectObjetEnum::options())
                    ->columns(2)
                    ->gridDirection('row')
                    ->helperText('Sélectionnez un ou plusieurs objets')
                    ->columnSpanFull(), // Prend toute la largeur

                Forms\Components\CheckboxList::make('type')
                    ->label('Type de bien')
                    ->options(ProspectTypeEnum::options())
                    ->columns(3)
                    ->gridDirection('row')
                    ->helperText('Sélectionnez un ou plusieurs types')
                    ->columnSpanFull(), // Prend toute la largeur

                Forms\Components\TextInput::make('budget')
                    ->label('Budget')
                    ->placeholder('Ex: 500€ - 800€/mois, 150 000€ - 200 000€')
                    ->maxLength(255)
                    ->helperText('Indiquez la fourchette budgétaire souhaitée')
                    ->columnSpan(1), // Prend 1 colonne sur 2

                Forms\Components\TextInput::make('secteur_localisation')
                    ->label('Secteur ou localisation')
                    ->placeholder('Ex: Liberté 5, Sacré Coeur 3, Centre-ville...')
                    ->maxLength(255)
                    ->helperText('Précisez le secteur ou la localisation préférée')
                    ->columnSpan(1), // Prend 1 colonne sur 2
            ])
            ->columns(2) // Changé de 1 à 2 colonnes
            ->collapsible(),
    ]);
}
public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('name')
                ->label('Nom')
                ->alignCenter()
                ->searchable()
                ->sortable()
                ->weight('medium'),

            Tables\Columns\TextColumn::make('email')
                ->label('Email')
                ->alignCenter()
                ->searchable()
                ->toggleable()
                ->copyable(),

            Tables\Columns\TextColumn::make('phone')
                ->label('Téléphone')
                ->alignCenter()
                ->searchable()
                ->toggleable()
                ->copyable(),

           // Alternative simple : afficher les tags comme du texte séparé par des virgules
Tables\Columns\TextColumn::make('objet_labels')
    ->label('Objets')
    ->alignCenter()
    ->toggleable()
    ->wrap()
    ->badge()
    ->formatStateUsing(function ($state) {
        if (is_array($state)) {
            return implode(', ', $state);
        }
        return $state;
    }),

Tables\Columns\TextColumn::make('type_labels')
    ->label('Types')
    ->alignCenter()
    ->toggleable()
    ->wrap()
    ->badge()
    ->formatStateUsing(function ($state) {
        if (is_array($state)) {
            return implode(', ', $state);
        }
        return $state;
    }),

            Tables\Columns\TextColumn::make('budget')
                ->label('Budget')
                ->alignCenter()
                ->toggleable()
                ->limit(30)
                ->tooltip(fn($record) => $record->budget),

            Tables\Columns\TextColumn::make('secteur_localisation')
                ->label('Secteur')
                ->alignCenter()
                ->searchable()
                ->toggleable()
                ->limit(20)
                ->tooltip(fn($record) => $record->secteur_localisation),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Créé le')
                ->dateTime('d/m/Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            SelectFilter::make('objet')
                ->label('Objet')
                ->options(ProspectObjetEnum::options())
                ->query(function (Builder $query, array $data): Builder {
                    return $data['value'] 
                        ? $query->byObjet($data['value'])
                        : $query;
                }),

            SelectFilter::make('type')
                ->label('Type')
                ->options(ProspectTypeEnum::options())
                ->query(function (Builder $query, array $data): Builder {
                    return $data['value']
                        ? $query->byType($data['value'])
                        : $query;
                }),

            Tables\Filters\Filter::make('secteur')
                ->form([
                    Forms\Components\TextInput::make('secteur_search')
                        ->label('Secteur')
                        ->placeholder('Rechercher par secteur...')
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['secteur_search'],
                            fn(Builder $query, $secteur): Builder => $query->bySecteur($secteur),
                        );
                }),
        ])
        ->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ])
        ->defaultSort('created_at', 'desc')
        ->poll('30s'); // Auto-refresh toutes les 30 secondes
}

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProspects::route('/'),
            'create' => Pages\CreateProspect::route('/create'),
            'view' => Pages\ViewProspect::route('/{record}'),
            'edit' => Pages\EditProspect::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes();
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'success';
    }
}