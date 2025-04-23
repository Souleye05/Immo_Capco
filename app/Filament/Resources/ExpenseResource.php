<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Expense;
use App\Models\Property;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
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
                Select::make('property_id')
                    ->label('Propriété')
                    ->relationship('property', 'name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        Select::make('type')
                            ->label('Type de propriété')
                            ->options([
                                'Immeuble' => 'Immeuble',
                                'Villa' => 'Villa',
                                'Bureau' => 'Bureau'
                            ])
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
                        return Property::create([
                            'type' => $data['type'],
                            'name' => $data['name'],
                            'address' => $data['address'],
                            'commission_value' => $data['commission_value'],
                            'commission_unit' => $data['commission_unit'],
                            'number_flat' => $data['number_flat'],
                        ])->id;
                    })
                    ->createOptionAction(function ($action) {
                        return $action
                            ->modalHeading('Créer une nouvelle propriété')
                            ->modalButton('Créer propriété')
                            ->modalWidth('lg');
                    }),
                Select::make('type')
                    ->label('Type de dépense')
                    ->options(['Biens' => 'Biens', 'Local' => 'Local'])
                    ->required(),
                MarkdownEditor::make('libelle')
                    ->columnSpan('full'),
                DatePicker::make('payment_date')
                    ->label('Date du paiement')
                    ->suffixIcon('heroicon-o-calendar-date-range')
                    ->native(false),
                TextInput::make('amount')
                    ->label('Montant')
                    ->numeric(),
                Select::make('payment_method')
                    ->label('Méthode de paiement')
                    ->searchable()
                    ->options([
                        'OM' => 'OM',
                        'Wave' => 'Wave',
                        'Free Money' => 'Free Money',
                        'Chèque' => 'Chèque',
                        'Virement' => 'Vrirement',
                        'Espèces' => 'Espèces',
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('property.name')
                    ->label('Propriété')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type de dépense')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('libelle')
                    ->label('Libellé')
                    ->limit(50)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Date du paiement')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->money('XOF')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Méthode de paiement')
                    ->searchable()
                    ->sortable(),
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}