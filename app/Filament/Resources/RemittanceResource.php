<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RemittanceResource\Pages;
use App\Filament\Resources\RemittanceResource\RelationManagers;
use App\Models\Remittance;
use Coolsam\FilamentFlatpickr\Forms\Components\Flatpickr;

use Coolsam\FilamentFlatpickr\Enums\FlatpickrMonthSelectorType;
use Filament\Forms;
use App\Models\Owner;
use App\Models\Property;
use App\Services\PaymentService;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\DatePicker;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RemittanceResource extends Resource
{
    protected static ?string $model = Remittance::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Paiement';

    public static ?string $label = 'Reversement';

    public static function form(Form $form): Form
    {
        return $form
            // ->schema([
                // Section::make('Informations du reversement')
                    ->schema([
                        Select::make('owner_id')
                            ->label('Propriétaire')
                            ->options(Owner::all()->pluck('name', 'id'))
                            ->searchable()
                            ->reactive()
                            ->required()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $owner = Owner::find($state);
                                if ($owner) {
                                    $propertyId = $owner->property_id;
                                    $set('property_id', $propertyId);

                                    $paymentService = app(PaymentService::class);
                                    $stats = $paymentService->getPropertyFinancialStats($propertyId, now()->month, now()->year);

                                    $set('commission', $stats['total_commissions']);
                                    $set('expenses', $stats['total_expenses']);
                                    $set('amount_to_transfer', $stats['amount_to_transfer']);
                                    
                                    // Initialiser à zéro car aucun versement n'a encore été effectué
                                    $set('amount', 0);
                                    $set('remaining', $stats['amount_to_transfer']);
                                    $set('status', 'Pending');
                                    $set('current_month', now()->month);
                                    $set('current_year', now()->year);
                                } else {
                                    $set('property_id', null);
                                    $set('commission', null);
                                    $set('expenses', null);
                                    $set('amount_to_transfer', null);
                                    $set('amount', 0);
                                    $set('remaining', null);
                                    $set('status', null);
                                    $set('current_month', null);
                                    $set('current_year', null);
                                }
                            }),

                        TextInput::make('property_id')
                            ->label('ID de la propriété')
                            ->disabled()
                            ->dehydrated(true)
                            ->required(),

                        TextInput::make('commission')
                            ->label('Commission')
                            ->disabled(),

                        TextInput::make('expenses')
                            ->label('Dépenses')
                            ->disabled(),

                        TextInput::make('amount_to_transfer')
                            ->label('Montant dû')
                            ->dehydrated(true)
                            ->disabled(),

                        TextInput::make('amount')
                            ->label('Montant versé')
                            ->default(0)
                            ->disabled()
                            ->dehydrated(true),

                        TextInput::make('remaining')
                            ->label('Reste à reverser')
                            ->dehydrated(true)
                            ->disabled(),

                        Select::make('status')
                            ->label('Statut')
                            ->options([
                                'Pending' => 'En attente',
                                'Partial' => 'Partiel',
                                'Paid' => 'Complet',
                            ])
                            
                            ->dehydrated(true)
                            ->disabled(),

                        Flatpickr::make('current_month')
                            // ->hidden()
                            ->label('Mois concerné')
                            ->monthSelect()
                            // ->default(now())
                            ->required(),
                            
                            
                        TextInput::make('current_year')
                            ->hidden()
                            ->dehydrated(true),
                            
                        DatePicker::make('remittance_date')
                            ->label('Date de création du reversement')
                            ->default(now())
                            ->required(),
             ]);

            
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Propriétaire')
                    ->searchable()
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('property_id')
                    ->label('Propriété ID')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_to_transfer')
                    ->label('Montant dû')
                    ->suffix(' FCFA')
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant déjà versé')
                    ->suffix(' FCFA')
                    ->color('success')
                    ->alignCenter()
                    ->sortable(),
                    Tables\Columns\TextColumn::make('remaining')
                    ->label('Reste à verser')
                    ->getStateUsing(function ($record) {
                        $alreadyPaid = Remittance::where('property_id', $record->property_id)->sum('amount');
                        $remaining = $record->amount_to_transfer - $alreadyPaid;

                        return number_format($remaining, 0, ',', ' ') . ' FCFA';
                    })
                    ->color('danger')
                    ->sortable(),
                    Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Paid' => 'success',
                        'Partial' => 'warning',
                        'Pending' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'Paid' => 'heroicon-o-check-circle',
                        'Partial' => 'heroicon-o-clock',
                        'Pending' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->default('Pending')
                    ->sortable(),
                Tables\Columns\TextColumn::make('remittance_date')
                    ->label('Date de création du reversement')
                    ->date('d/m/Y')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('current_month')
                    ->label('Mois concerné')
                    // format month en français (ex: Janvier, Février, ...) et l'annee
                    ->getStateUsing(function ($record) {
                        return now()->locale('fr')->monthName;
                    })
                    ->alignCenter()
                    ->sortable(),
                    Tables\Columns\TextColumn::make('current_year')
                    ->label('Annee')
                    // format month en français (ex: Janvier, Février, ...) et l'annee
                    ->getStateUsing(function ($record) {
                        return now()->year;

                    })
                    ->sortable(),
                   
                
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
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
            RelationManagers\RemittancePartialRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRemittances::route('/'),
            'create' => Pages\CreateRemittance::route('/create'),
            'edit' => Pages\EditRemittance::route('/{record}/edit'),
            // 'view' => Pages\ViewRemittance::route('/{record}'),
        ];
    }
    
}