<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers\VersementRelationManager;
use App\Models\Payment;
use App\Models\Flat;
use App\Models\Tenant;
use Coolsam\FilamentFlatpickr\Forms\Components\Flatpickr;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Paiement';

    protected $listeners = ['refresh' => '$refresh'];

    public static ?string $label = 'facture';

    public static function form(Form $form): Form
    {

        return $form
            ->schema([


                TextInput::make('numero')
                    ->label('Numéro de facture')
                    ->default('FAC-' . random_int(100000, 999999))
                    ->disabled()
                    ->dehydrated(true)
                    ->required(),

                 Select::make('tenant_id')
                    ->label('Locataire')
                    ->options(Tenant::all()->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) =>
                     $set('flat_id', Flat::find($state)?->id ?? 0) and
                     $set('amount', Flat::find($state)?->loyer ?? 0) ),


                    Select::make('flat_id')
                    ->label('Appartement')
                    ->relationship('flat', 'reference'),
                    // ->dehydrated(true),
                

                TextInput::make('amount')
                    ->label('Montant')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(true),

                Flatpickr::make('current_month')
                ->monthSelect(),
                // ->dateFormat('Y-m')                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             ,

                DatePicker::make('date_payment')
                    ->label('Date du paiement'),
                    
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


                Toggle::make('status')
                    ->label('Etat du paiement')
                    ->inline(false)
                    ->disabled()
                    ->dehydrated(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('numero')
                    ->label('Numéro de facture')
                    ->searchable(),

                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Locataire')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                    TextColumn::make('current_month')
                    ->label('Mois de'),
                    // ->date('Y-m'),
                TextColumn::make('date_payment')
                    ->label('Date de paiement'),
                    // ->date()
                TextColumn::make('payment_method')
                    ->label('Méthode de paiement'),
                TextColumn::make('amount')
                    ->label('Montant')
                    ->money('xof'),

                // ✅ Colonne statut dynamique
                Tables\Columns\IconColumn::make('status')
                    ->label('Statut')
                    ->sortable()
                    ->boolean()  // Ceci convertit automatiquement 1/0 en icônes
                    ->trueIcon('heroicon-o-check-circle')  // Icône pour paiement complet
                    ->falseIcon('heroicon-o-x-circle')     // Icône pour paiement incomplet
                    ->trueColor('success')                 // Couleur verte pour paiement complet
                    ->falseColor('danger')                 // Couleur rouge pour paiement incomplet
                    ->toggleable(),
            
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
            VersementRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
