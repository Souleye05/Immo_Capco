<?php

namespace App\Filament\Resources;

use App\Enums\PaymentType;
use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers\VersementRelationManager;
use App\Models\Payment;
use App\Models\Flat;
use App\Models\Tenant;
use App\Services\FactureService;
use Coolsam\FilamentFlatpickr\Forms\Components\Flatpickr;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
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
                // Type de facture
                Select::make('type')
                    ->label('Type de facture')
                    ->options(PaymentType::getOptions())
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, Get $get) {
                        if (!$state) return;

                        $type = PaymentType::from($state);
                        
                        // Générer le numéro selon le type
                        $service = app(FactureService::class);
                        $set('numero', $service->generateUniqueNumero($type));

                        // Recalculer le montant si on a déjà un locataire
                        $tenantId = $get('tenant_id');
                        if ($tenantId) {
                            $tenant = Tenant::with('flat')->find($tenantId);
                            if ($tenant && $tenant->flat) {
                                $amount = $service->calculateAmountByType($type, $tenant->flat);
                                $set('amount', $amount);
                                $set('amount_remaining', $amount);
                            }
                        }
                    }),
                TextInput::make('numero')
                    ->label('Numéro de facture')
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
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        if (!$state) return;

                        $tenant = Tenant::with('flat')->find($state);
                        
                        if ($tenant && $tenant->flat) {
                            $set('flat_id', $tenant->flat->id);
                            
                            // Calculer le montant selon le type sélectionné
                            $typeValue = $get('type');
                            if ($typeValue) {
                                $type = PaymentType::from($typeValue);
                                $service = app(FactureService::class);
                                
                                // Vérifier si on peut créer ce type de paiement
                                $validation = $service->canCreatePayment($type, $tenant->id, $tenant->flat->id);
                                
                                if (!$validation['can_create']) {
                                    Notification::make()
                                        ->warning()
                                        ->title('Attention')
                                        ->body($validation['message'])
                                        ->send();
                                    
                                    $set('tenant_id', null);
                                    return;
                                }
                                
                                $amount = $service->calculateAmountByType($type, $tenant->flat);
                                $set('amount', $amount);
                                $set('amount_remaining', $amount);
                            }
                        } else {
                            $set('flat_id', null);
                            $set('amount', 0);
                            $set('amount_remaining', 0);
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
                    ->dehydrated(true)
                    ->suffix('FCFA'),

                Flatpickr::make('current_month')
                    ->label('Mois concerné')
                    ->monthSelect()
                    ->visible(fn (Get $get): bool => 
                        $get('type') === PaymentType::LOYER->value
                    )
                    ->required(fn (Get $get): bool => 
                        $get('type') === PaymentType::LOYER->value
                    ),

                DatePicker::make('date_payment')
                    ->label('Date du paiement')
                    ->required()
                    ->default(now()),

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
                    ->alignCenter()
                    ->searchable()
                    ->sortable(),
                    // Badge pour le type
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn (PaymentType $state): string => $state->getLabel())
                    ->colors([
                        'success' => PaymentType::LOYER->value,
                        'warning' => PaymentType::CAUTION->value,
                        'info' => PaymentType::COMMISSION->value,
                    ])
                    ->sortable(),

                // Modification pour faire un clic sur le nom du locataire pour afficher les détails
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Locataire')
                    ->searchable()
                    ->alignCenter()
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
                    ->label('Mois de')
                    ->alignCenter()
                    ->placeholder('N/A')
                    ->toggleable(),
                                    
                TextColumn::make('amount')
                    ->label('Montant dû')                
                    ->formatStateUsing(fn ($state): string => number_format($state, 0, ',', ' ') . ' FCFA')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('amount_paid')
                    ->label('Montant versé')                    
                    ->formatStateUsing(fn ($state): string => number_format($state, 0, ',', ' ') . ' FCFA')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('amount_remaining')
                    ->label('Montant restant')
                    
                    ->formatStateUsing(fn ($state): string => number_format($state, 0, ',', ' ') . ' FCFA')
                    ->alignCenter()
                    ->sortable(),

                // Colonne statut dynamique
                Tables\Columns\IconColumn::make('status')
                    ->label('Statut')
                    ->sortable()
                    ->alignCenter()
                    ->boolean()           
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(),

                
            ])
            ->filters([
                //
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type de facture')
                    ->options(PaymentType::getOptions()),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        '1' => 'Payé',
                        '0' => 'Non payé',
                    ]),
            ])
            ->actions([
                // Tables\Actions\Action::make('download_quittance')
                //     ->label('Quittance')
                //     ->icon('heroicon-o-document-check')
                //     ->color('success')
                //     ->visible(function (Payment $record): bool {
                //         // Visible seulement si le paiement est complet
                //         return $record->is_fully_paid;
                //     })
                //     ->action(function (Payment $record) {
                //         return response()->redirectToRoute('documents.download-quittance', $record);
                //     })
                //     ->tooltip(function (Payment $record): string {
                //         $versementsCount = $record->versement()->count();
                        
                //         if ($versementsCount <= 1) {
                //             return 'Télécharger la quittance simple';
                //         }
                        
                //         return "Télécharger la quittance détaillée ({$versementsCount} versements)";
                //     }),
                Tables\Actions\Action::make('download_quittance')
                    ->label('Quittance')
                    ->icon('heroicon-o-document-check')
                    ->color('success')
                    ->visible(fn (Payment $record): bool => $record->is_fully_paid)
                    ->url(fn (Payment $record) => route('documents.download-quittance', $record))
                    ->openUrlInNewTab()
                    ->tooltip(function (Payment $record): string {
                        $versementsCount = $record->versement()->count();
                        
                        return $versementsCount <= 1
                            ? 'Télécharger la quittance simple'
                            : "Télécharger la quittance détaillée ({$versementsCount} versements)";
    }),

                Tables\Actions\EditAction::make(),               
            // Bouton explicite pour voir les détails du locataire
                Tables\Actions\Action::make('viewTenantDetails')
                    ->label('Détails locataire')
                    ->icon('heroicon-o-user')
                    ->color('info')
                    ->modalHeading(fn (Payment $record): string => 'Détails du locataire: ' . $record->tenant->name)
                    ->modalContent(function (Payment $record) {
                        // Récupérer le locataire avec la relation flat
                        $tenant = Tenant::with('flat')->find($record->tenant_id);
                        $flat = $tenant->flat ?? null;
                        
                        return view('filament.resources.payment-resource.tenant-details', [
                            'tenant' => $tenant,
                            'flat' => $flat,
                        ]);
                    })
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