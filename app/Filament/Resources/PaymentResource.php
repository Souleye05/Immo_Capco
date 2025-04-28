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
use Filament\Support\Enums\FontWeight;

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
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Nom & Prénoms du locataire')
                            ->required(),
                        TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->required(),
                        TextInput::make('address')
                            ->label('Adresse')
                            ->required(),
                    ])
                    ->createOptionUsing(function ($data) {
                        $tenant = Tenant::create($data);
                        return $tenant->id;
                    })
                    ->createOptionAction(function ($action) {
                        return $action
                            ->modalHeading('Créer un nouveau locataire')
                            ->modalButton('Créer locataire')
                            ->modalWidth('lg');
                    })
                    ->afterStateUpdated(function ($state, callable $set) {
                        // On récupère le locataire sélectionné
                        $tenant = Tenant::with('flat')->find($state);
                    
                        if ($tenant && $tenant->flat) {
                            $set('flat_id', $tenant->flat->id);
                            $set('amount', $tenant->flat->loyer);
                        } else {
                            $set('flat_id', null);
                            $set('amount', 0);
                        }
                    }),
                    
                    Select::make('flat_id')
                    ->label('Appartement')
                    ->relationship('flat', 'reference')
                    ->disabled()
                    ->dehydrated(true),
                
                TextInput::make('amount')
                    ->label('Montant')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(true),

                Flatpickr::make('current_month')
                ->monthSelect(),

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

                // Modification pour faire un clic sur le nom du locataire pour afficher les détails
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Locataire')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->weight(FontWeight::Bold)
                    ->action(
                        Tables\Actions\Action::make('viewTenantDetails')
                            ->label('Voir les détails')
                            ->modalHeading(fn (Payment $record): string => 'Détails du locataire: ' . $record->tenant->name)
                            ->modalWidth('md')
                            ->modalContent(function (Payment $record) {
                                $tenant = $record->tenant;
                                // $flat = $tenant->flat ?? null;
                                
                                return view('filament.resources.payment-resource.tenant-details', [
                                    'tenant' => $tenant,
                                    // 'flat' => $flat,
                                ]);
                            })
                    ),

                TextColumn::make('current_month')
                    ->label('Mois de'),
                    
                TextColumn::make('date_payment')
                    ->label('Date de paiement'),
                    
                TextColumn::make('payment_method')
                    ->label('Méthode de paiement'),
                    
                TextColumn::make('amount')
                    ->label('Montant')
                    ->money('xof'),

                // Colonne statut dynamique
                Tables\Columns\IconColumn::make('status')
                    ->label('Statut')
                    ->sortable()
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                    // Bouton pour voir uniquement les détails du locataire
                Tables\Actions\Action::make('viewTenantDetails')
                    ->label('Détails locataire')
                    ->icon('heroicon-o-user')
                    ->color('info')
                    ->modalHeading(fn (Payment $record): string => 'Détails du locataire: ' . $record->tenant->name)
                    ->modalContent(function (Payment $record) {
                            // Récupérer le locataire
                        $tenant = Tenant::find($record->tenant_id);
        
                        return view('filament.resources.payment-resource.tenant-details', [
                            'tenant' => $tenant,
                        ]);
                    })
    ->modalWidth('md'),
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