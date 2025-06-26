<?php

namespace App\Filament\Resources;

use App\Enums\PropertyType;
use App\Models\CategorieDepense;
use App\Models\DepenseType;
use App\Models\Prestataire;
use Filament\Forms;
use Filament\Tables;
use App\Models\Expense;
use App\Models\Property;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\MarkdownEditor;
use App\Filament\Resources\ExpenseResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ExpenseResource\RelationManagers;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-down';
    protected static ?string $navigationGroup = 'Paiement';

    public static ?string $label = 'dépenses';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Propriété et Appartement côte à côte
                Forms\Components\Grid::make(2)
                    ->schema([
                        // Sélection de propriété
                        Select::make('property_id')
                            ->label('Propriété')
                            ->options(Property::all()->pluck('full_name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live() // Garde la réactivité
                            ->afterStateUpdated(fn (callable $set) => $set('flat_id', null))
                            ->createOptionForm([
                                // Formulaire de création de propriété inchangé
                                Select::make('type')
                                    ->label('Type de propriété')
                                    ->options(PropertyType::getOptions())
                                    ->required(),
                                TextInput::make('name')
                                    ->label('Nom & Prénoms')
                                    ->required(),
                                TextInput::make('address')
                                    ->label('Adresse')
                                    ->required(),
                                TextInput::make('commission_value')
                                    ->label('Commission sur la propriété')
                                    ->numeric(),
                                Select::make('commission_unit')
                                    ->label('Unité')
                                    ->options([
                                        '%' => '%',
                                        'F CFA' => 'F CFA'
                                    ])
                                    ->required(),
                                TextInput::make('number_flat')
                                    ->label("Nombre d'appartement dans la propriété")
                                    ->numeric()
                                    ->minValue(1),
                            ])
                            ->createOptionUsing(function (array $data) {
                                return Property::create($data)->id;
                            })
                            ->createOptionAction(function ($action) {
                                return $action
                                    ->modalHeading('Créer une nouvelle propriété')
                                    ->modalButton('Créer propriété')
                                    ->modalWidth('lg');
                            }),
    
                        // Sélection d'appartement (affiché en même temps)
                        Select::make('flat_id')
                            ->label('Appartement')
                            ->options(function (callable $get) {
                                $propertyId = $get('property_id');
                                if (!$propertyId) {
                                    return [];
                                }
                        
                                $property = Property::with('flats')->find($propertyId);
                        
                                if (!$property || $property->flats->isEmpty()) {
                                    return [];
                                }
                        
                                return $property->flats->mapWithKeys(function ($flat) {
                                    $label = $flat->type->label();

                                    if ($flat->level) {
                                        $label .= " ({$flat->level})";
                                    }
                                    return [
                                        $flat->id => $label,
                                    ];
                                })->toArray();
                            })
                            ->searchable()
                            ->hint('Sélectionnez l\'appartement concerné')
                            // Ne cachez plus le champ, il est toujours visible
                            // ->hidden(fn (callable $get) => !$get('property_id'))
                            ->disabled(fn (callable $get) => !$get('property_id')) // Désactiver plutôt que cacher
                            ->required(),
                    ]),
    
                // Le reste du formulaire sans changement, mais sans Fieldsets
                Select::make('prestataire_id')
                    ->label('Prestataire')
                    ->options(function () {
                        return Prestataire::all()->pluck('full_name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        TextInput::make('nom')
                            ->label('Nom du prestataire')
                            ->required(),
                        TextInput::make('profession')
                            ->label('Profession')
                            ->required(),
                        TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->required(),
                        TextInput::make('adresse')
                            ->label('Adresse')
                            ->required(),
                    ])
                    ->createOptionUsing(function (array $data) {
                        return Prestataire::create($data)->id;
                    })
                    ->createOptionAction(function ($action) {
                        return $action
                            ->modalHeading('Créer un nouveau prestataire')
                            ->modalButton('Créer prestataire')
                            ->modalWidth('lg');
                    }),
    
                Select::make('categorie_depense_id')
                    ->label('Catégorie de Dépense')
                    ->relationship('categorie', 'categorie')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        TextInput::make('categorie')
                            ->label('Catégorie')
                            ->required(),
                        TextInput::make('description')
                            ->label('Description')
                            ->required(),
                    ])
                    ->createOptionUsing(function (array $data) {
                        return CategorieDepense::create($data)->id;
                    })
                    ->createOptionAction(function ($action) {
                        return $action
                            ->modalHeading('Créer un catégorie de dépense')
                            ->modalButton('Créer type')
                            ->modalWidth('md');
                    }),
                    
                TextInput::make('titre')
                    ->label('Titre')
                    ->required()
                    ->helperText('Entrez le titre de la dépense'),
                
                MarkdownEditor::make('libelle')
                    ->label('Libellé')
                    ->required(),
    
                DatePicker::make('payment_date')
                    ->label('Date du paiement')
                    ->suffixIcon('heroicon-o-calendar-date-range')
                    ->native(false)
                    ->default(now())
                    ->required(),
    
                TextInput::make('amount')
                    ->label('Montant')
                    ->numeric()
                    ->minValue(0)
                    ->step(100)
                    ->required()
                    ->suffix('XOF')
                    ->helperText('Entrez le montant en F CFA'),
    
                Select::make('payment_method')
                    ->label('Méthode de paiement')
                    ->searchable()
                    ->required()
                    ->options([
                        'OM' => 'Orange Money',
                        'Wave' => 'Wave',
                        'Free Money' => 'Free Money',
                        'Chèque' => 'Chèque',
                        'Virement' => 'Virement',
                        'Espèces' => 'Espèces',
                    ]),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('property.full_name')
                    ->label('Propriété')
                    ->searchable()
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('flat.type')
                    ->label('Appartement')
                    ->searchable()
                    ->alignCenter()
                    ->sortable(),  
                Tables\Columns\TextColumn::make('prestataire.full_name')
                    ->label('Prestataire')
                    ->searchable()
                    ->alignCenter()
                    ->sortable(), 
                Tables\Columns\TextColumn::make('categorie.categorie')
                    ->label('Catégorie de dépense')
                    ->searchable()
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('titre')
                    ->label('Titre')
                    ->searchable()
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('libelle')
                    ->label('Libellé')
                    ->limit(50)
                    ->alignCenter()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Date du paiement')
                    ->date('d/m/Y')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->suffix(' F CFA')
                    ->alignCenter()            
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Méthode de paiement')
                    ->searchable()
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Mis à jour le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
    
    // Vous pouvez personnaliser les messages d'en-tête
    public static function getModelLabel(): string
    {
        return 'Dépense';
    }
    
    public static function getPluralModelLabel(): string
    {
        return 'Dépenses';
    }
    
}