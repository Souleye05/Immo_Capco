<?php

namespace App\Filament\Resources;

use App\Enums\PaymentType;
use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers\VersementRelationManager;
use App\Models\Payment;
use App\Models\Flat;
use App\Models\Tenant;
use App\Models\Contract;
use App\Services\DocumentGeneratorService;
use App\Services\FactureService;
use Carbon\Carbon;
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
use Filament\Forms\Components\Placeholder;
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
                            $tenant = Tenant::with('flatThroughContract')->find($tenantId);
                            if ($tenant && $tenant->flatThroughContract) {
                                $amount = $service->calculateAmountByType($type, $tenant->flatThroughContract);
                                $set('amount', $amount);
                                $set('amount_remaining', $amount);
                            }
                        }

                        // Vérifier la validation si on a tous les éléments
                        self::validatePaymentOnTypeChange($get, $set, $service);
                    }),

                TextInput::make('numero')
                    ->label('Numéro de facture')
                    ->disabled()
                    ->dehydrated(true)
                    ->required(),

                Select::make('tenant_id')
                    ->label('Locataire')
                    ->options(fn(): array => Tenant::pluck('name', 'id')->toArray())
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
                        if (!$state) {
                            $set('flat_id', null);
                            $set('contract_id', null);
                            $set('amount', 0);
                            $set('amount_remaining', 0);
                            return;
                        }

                        $tenant = Tenant::with('flatThroughContract')->find($state);

                        if ($tenant && $tenant->flatThroughContract) {
                            $set('flat_id', $tenant->flatThroughContract->id);
                            $set('contract_id', null);

                            // Calculer le montant selon le type sélectionné
                            $typeValue = $get('type');
                            if ($typeValue) {
                                $type = PaymentType::from($typeValue);
                                $service = app(FactureService::class);

                                // Vérification de base (caution)
                                $validation = $service->canCreatePayment($type, $tenant->id, $tenant->flatThroughContract->id);

                                if (!$validation['can_create']) {
                                    Notification::make()
                                        ->warning()
                                        ->title('Attention')
                                        ->body($validation['message'])
                                        ->send();

                                    $set('tenant_id', null);
                                    $set('flat_id', null);
                                    $set('contract_id', null);
                                    return;
                                }

                                $amount = $service->calculateAmountByType($type, $tenant->flatThroughContract);
                                $set('amount', $amount);
                                $set('amount_remaining', $amount);
                            }
                        } else {
                            $set('flat_id', null);
                            $set('contract_id', null);
                            $set('amount', 0);
                            $set('amount_remaining', 0);
                        }
                    }),

                Select::make('flat_id')
                    ->label('Appartement')
                    ->relationship('flat', 'reference')
                    ->disabled()
                    ->dehydrated(true),

                Select::make('contract_id')
                    ->label('Contrat')
                    ->required()
                    ->searchable()
                    ->reactive()
                    ->options(function (Get $get) {
                        $tenantId = $get('tenant_id');
                        if (!$tenantId) return [];

                        return Contract::where('tenant_id', $tenantId)
                            ->get()
                            ->mapWithKeys(function ($contract) {
                                $label = $contract->contract_number ?? "Contrat #{$contract->id}";
                                $status = $contract->status ?? 'inconnu';
                                $property = $contract->property->name ?? 'Inconnu';

                                $flat = $contract->flat->level ?? 'Inconnu';
                                $statusLabel = match ($status) {
                                    'active' => '✅ Actif',
                                    'expired' => '⏰ Expiré',
                                    'terminated' => '❌ Terminé',
                                    default => "📋 Actif"
                                };
                                return [$contract->id => "{$label} - {$statusLabel} ({$property} - {$flat})"];
                            })
                            ->toArray();
                    })
                    ->disabled(fn(Get $get): bool => !$get('tenant_id'))
                    ->helperText(function (Get $get): string {
                        if (!$get('tenant_id')) {
                            return 'Sélectionnez d\'abord un locataire';
                        }

                        $tenantId = $get('tenant_id');
                        $contractsCount = Contract::where('tenant_id', $tenantId)->count();

                        if ($contractsCount === 0) {
                            return '⚠️ Aucun contrat trouvé pour ce locataire';
                        }

                        $activeCount = Contract::where('tenant_id', $tenantId)
                            ->where('status', 'active')
                            ->count();
                        return "📋 {$contractsCount} contrat(s) disponible(s) dont {$activeCount} actif(s)";
                    })
                    ->placeholder('Sélectionnez un contrat')
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        if (!$state) return;

                        $contract = Contract::find($state);
                        if ($contract) {
                            // Notification selon le statut du contrat
                            match ($contract->status) {
                                'active' => Notification::make()
                                    ->success()
                                    ->title('Contrat actif sélectionné')
                                    ->body("Contrat {$contract->contract_number} - Statut: Actif")
                                    ->send(),
                                'expired' => Notification::make()
                                    ->warning()
                                    ->title('Attention: Contrat expiré')
                                    ->body("Le contrat {$contract->contract_number} a expiré")
                                    ->send(),
                                'terminated' => Notification::make()
                                    ->warning()
                                    ->title('Attention: Contrat terminé')
                                    ->body("Le contrat {$contract->contract_number} est terminé")
                                    ->send(),
                                default => null,
                            };
                        }

                        // Vérifier la validation quand le contrat change
                        self::validatePaymentOnContractChange($get, $set);
                    }),

                // Placeholder pour afficher les informations de validation
                Placeholder::make('validation_info')
                    ->label('Informations sur les factures existantes')
                    ->content(function (Get $get): string {
                        $contractId = $get('contract_id');
                        $month = $get('current_month');
                        $type = $get('type');

                        if (!$contractId || !$month || !$type) {
                            return 'Sélectionnez un contrat et un mois pour voir les informations.';
                        }

                        $service = app(FactureService::class);
                        $paymentType = PaymentType::from($type);
                        $validation = $service->validatePaymentCreation($paymentType, $contractId, $month);

                        if (!empty($validation['existing_payments'])) {
                            $list = collect($validation['existing_payments'])
                                ->map(fn($p) => "• {$p['type']} (N° {$p['numero']}) - " . number_format($p['amount'], 0, ',', ' ') . ' FCFA')
                                ->implode("\n");

                            return "Factures existantes pour ce mois :\n{$list}";
                        }

                        return 'Aucune facture existante pour ce mois.';
                    })
                    ->visible(function (Get $get): bool {
                        return $get('contract_id') && $get('current_month') && $get('type');
                    }),

                TextInput::make('amount')
                    ->label('Montant')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(true)
                    ->suffix('FCFA'),

                Flatpickr::make('current_month')
                    ->label(function (Get $get): string {
                        $typeValue = $get('type');
                        if ($typeValue) {
                            $type = PaymentType::from($typeValue);
                            return $type->getMonthLabel();
                        }
                        return 'Mois concerné';
                    })
                    ->monthSelect()
                    ->reactive()
                    ->visible(function (Get $get): bool {
                        $typeValue = $get('type');
                        if ($typeValue) {
                            $type = PaymentType::from($typeValue);
                            return $type->requiresMonth();
                        }
                        return false;
                    })
                    ->required(function (Get $get): bool {
                        $typeValue = $get('type');
                        if ($typeValue) {
                            $type = PaymentType::from($typeValue);
                            return $type->requiresMonth();
                        }
                        return false;
                    })
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        // Vérifier la validation quand le mois change
                        self::validatePaymentOnMonthChange($get, $set);
                    }),

                DatePicker::make('date_payment')
                    ->label('Date du paiement')
                    ->required()
                    ->default(now())
                    ->maxDate(now()->addDays(30))
                    ->minDate(now()->subYear()),

                Toggle::make('status')
                    ->label('Etat du paiement')
                    ->inline(false)
                    ->disabled()
                    ->dehydrated(true),
            ]);
    }

    /**
     * Valide le paiement quand le type change
     */
    private static function validatePaymentOnTypeChange(Get $get, Set $set, FactureService $service): void
    {
        $contractId = $get('contract_id');
        $month = $get('current_month');
        $type = $get('type');

        if (!$contractId || !$month || !$type) return;

        $paymentType = PaymentType::from($type);
        $validation = $service->validatePaymentCreation($paymentType, $contractId, $month);

        if (!$validation['can_create']) {
            Notification::make()
                ->danger()
                ->title('Facture déjà existante')
                ->body($validation['message'])
                ->persistent()
                ->send();
        }
    }

    /**
     * Valide le paiement quand le contrat change
     */
    private static function validatePaymentOnContractChange(Get $get, Set $set): void
    {
        $contractId = $get('contract_id');
        $month = $get('current_month');
        $type = $get('type');

        if (!$contractId || !$month || !$type) return;

        $service = app(FactureService::class);
        $paymentType = PaymentType::from($type);
        $validation = $service->validatePaymentCreation($paymentType, $contractId, $month);

        if (!$validation['can_create']) {
            Notification::make()
                ->danger()
                ->title('Facture déjà existante')
                ->body($validation['message'])
                ->persistent()
                ->send();
        } elseif (!empty($validation['message'])) {
            Notification::make()
                ->info()
                ->title('Information')
                ->body($validation['message'])
                ->send();
        }
    }

    /**
     * Valide le paiement quand le mois change
     */
    private static function validatePaymentOnMonthChange(Get $get, Set $set): void
    {
        self::validatePaymentOnContractChange($get, $set);
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

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn(PaymentType $state): string => $state->getLabel())
                    ->colors([
                        'success' => PaymentType::LOYER->value,
                        'warning' => PaymentType::CAUTION->value,
                        'info' => PaymentType::COMMISSION->value,
                    ])
                    ->sortable(),

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
                            ->modalHeading(fn(Payment $record): string => 'Détails du locataire: ' . $record->tenant->name)
                            ->modalWidth('md')
                            ->modalContent(function (Payment $record) {
                                $tenant = $record->tenant;

                                return view('filament.resources.payment-resource.tenant-details', [
                                    'tenant' => $tenant,
                                ]);
                            })
                    ),

                Tables\Columns\TextColumn::make('contract.contract_number')
                    ->label('Contrat')
                    ->alignCenter()
                    ->placeholder('N/A')
                    ->formatStateUsing(function ($state, $record) {
                        if (!$record->contract) return 'N/A';

                        $status = $record->contract->status ?? 'inconnu';
                        $icon = match ($status) {
                            'active' => '✅',
                            'expired' => '⏰',
                            'terminated' => '❌',
                            default => '📋'
                        };

                        return "{$state} {$icon}";
                    })
                    ->toggleable(),

                TextColumn::make('flat.property.name')
                    ->label('Bien immobilier')
                    ->alignCenter()
                    ->searchable()
                    ->sortable()
                    ->toggleable(),


                TextColumn::make('current_month')
                    ->label('Mois de')
                    ->alignCenter()
                    ->sortable()
                    ->placeholder('N/A')
                    ->formatStateUsing(fn($state): string => Carbon::createFromFormat('Y-m', $state)->translatedFormat('F Y'))
                    ->toggleable(),

                TextColumn::make('amount')
                    ->label('Montant dû')
                    ->formatStateUsing(fn($state): string => number_format($state, 0, ',', ' ') . ' FCFA')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('amount_paid')
                    ->label('Montant versé')
                    ->formatStateUsing(fn($state): string => number_format($state, 0, ',', ' ') . ' FCFA')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('amount_remaining')
                    ->label('Montant restant')
                    ->formatStateUsing(fn($state): string => number_format($state, 0, ',', ' ') . ' FCFA')
                    ->alignCenter()
                    ->sortable(),

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
                Tables\Filters\SelectFilter::make('current_month')
                    ->label('Mois concerné')
                    ->options(function () {
                        return Payment::query()
                            ->whereNotNull('current_month')
                            ->select('current_month')
                            ->distinct()
                            ->orderByDesc('current_month')
                            ->pluck('current_month')
                            ->filter(fn ($val) => preg_match('/^\d{4}-\d{2}$/', $val)) // sécurité : Y-m uniquement
                            ->mapWithKeys(fn ($value) => [
                                $value => \Carbon\Carbon::createFromFormat('Y-m', $value)->translatedFormat('F Y')
                            ])
                            ->toArray();
                    }),


                Tables\Filters\SelectFilter::make('type')
                    ->label('Type de facture')
                    ->options(PaymentType::getOptions()),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        '1' => 'Payé',
                        '0' => 'Non payé',
                    ]),

                Tables\Filters\SelectFilter::make('contract_id')
                    ->label('Contrat')
                    ->relationship('contract', 'contract_number'),

                Tables\Filters\SelectFilter::make('contract_status')
                    ->label('Statut du contrat')
                    ->query(function ($query, $data) {
                        if ($data['value']) {
                            $query->whereHas('contract', function ($q) use ($data) {
                                $q->where('status', $data['value']);
                            });
                        }
                    })
                    ->options([
                        'active' => 'Actif',
                        'expired' => 'Expiré',
                        'terminated' => 'Terminé',
                    ]),
            ])
            ->actions([
    Tables\Actions\ActionGroup::make([
        Tables\Actions\EditAction::make(),

      Tables\Actions\Action::make('facture_loyer')
    ->label('Facture')
    ->icon('heroicon-o-document-text')
    ->color('primary')
    ->visible(fn(Payment $record): bool => 
        app(DocumentGeneratorService::class)->canGenerateFacture($record)
    )
    ->url(fn(Payment $record) => route('documents.download-facture', $record))
    ->openUrlInNewTab(),
        Tables\Actions\Action::make('download_quittance')
            ->label('Quittance')
            ->icon('heroicon-o-document-check')
            ->color('success')
            ->visible(fn(Payment $record): bool => $record->is_fully_paid)
            ->url(fn(Payment $record) => route('documents.download-quittance', $record))
            ->openUrlInNewTab(),

        Tables\Actions\Action::make('viewTenantDetails')
            ->label('Détails locataire')
            ->icon('heroicon-o-user')
            ->color('info')
            ->modalHeading(fn(Payment $record): string => 'Détails du locataire: ' . $record->tenant->name)
            ->modalContent(function (Payment $record) {
                $tenant = Tenant::with('flatThroughContract')->find($record->tenant_id);
                $flat = $tenant->flatThroughContract ?? null;

                return view('filament.resources.payment-resource.tenant-details', [
                    'tenant' => $tenant,
                    'flat' => $flat,
                ]);
            }),
    ]),
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
