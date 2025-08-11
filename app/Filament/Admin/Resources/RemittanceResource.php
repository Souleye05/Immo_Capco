<?php

namespace App\Filament\Admin\Resources;

use App\Enums\RemittanceType;
use App\Filament\Admin\Resources\RemittanceResource\Pages;
use App\Filament\Admin\Resources\RemittanceResource\RelationManagers;
use App\Models\Remittance;
use App\Traits\HasAgencyPermissions;
use Coolsam\FilamentFlatpickr\Forms\Components\Flatpickr;
use Illuminate\Support\Collection;
use Coolsam\FilamentFlatpickr\Enums\FlatpickrMonthSelectorType;
use Filament\Forms;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Tenant;
use App\Services\FactureService;
use App\Services\PaymentService;
use App\Services\RemittanceValidationService;
use Closure;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\DatePicker;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class RemittanceResource extends Resource
{
    use HasAgencyPermissions;
    protected static ?string $model = Remittance::class;

    // Désactiver le tenant scoping automatique
    protected static ?string $tenantOwnershipRelationshipName = null;

    // Désactiver complètement le tenant scoping
    public static function isScopedToTenant(): bool
    {
        return false;
    }

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Paiement';

    public static ?string $label = 'Reversement';

    // Permissions spécifiques pour la gestion des reversements
    protected static function getViewPermission(): string
    {
        return 'view_financial_reports';
    }

    protected static function getCreatePermission(): string
    {
        return 'manage_remittances';
    }

    protected static function getEditPermission(): string
    {
        return 'manage_remittances';
    }

    protected static function getDeletePermission(): string
    {
        return 'manage_remittances';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('remittance_type')
                    ->label('Type de reversement')
                    ->options(RemittanceType::getOptions())
                    ->reactive()
                    ->required()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        if (!$state) return;
                        // Générer le numéro selon le type
                        $service = app(FactureService::class);
                        $set('numero', $service->generateUniqueNumber(RemittanceType::from($state)));

                        Notification::make()
                            ->title('Type de reversement sélectionné')
                            ->body($get('Le numero a été généré automatiquement avec succès'))
                            ->success()
                            ->send();

                        // Reset les champs quand le type change
                        $set('commission', 0);
                        $set('expenses', 0);
                        $set('amount_to_transfer', 0);
                        $set('remaining', 0);
                        $set('amount', 0);

                        // Recalculer si on a déjà un propriétaire sélectionné
                        $ownerId = $get('owner_id');
                        if ($ownerId) {
                            self::calculateRemittanceAmount($state, $ownerId, $get('tenant_id'), $set, $get);
                        }
                    }),

                TextInput::make('numero')
                    ->label('Numéro de reversement')
                    ->disabled()
                    ->dehydrated(true)
                    ->required(),

                Flatpickr::make('current_month')
                    ->label('Mois concerné')
                    ->monthSelect()
                    ->required()
                    ->visible(fn(callable $get) => $get('remittance_type') === 'loyer')
                    ->reactive()
                    ->rules([
                        function (callable $get) {
                            return function (string $attribute, $value, Closure $fail) use ($get) {
                                $remittanceType = $get('remittance_type');
                                $ownerId = $get('owner_id');
                                $year = $get('current_year') ?? now()->year;

                                if ($remittanceType === 'loyer' && $ownerId && $value) {
                                    // Récupérer la propriété du propriétaire
                                    $owner = Owner::find($ownerId);

                                    if ($owner && $owner->property()) {
                                        $property = $owner->property();
                                        // Vérifier si un reversement de loyer existe déjà pour ce mois, cette année et cette propriété
                                        $exists = Remittance::where('property_id', $property->id)
                                            ->where('remittance_type', 'loyer')
                                            ->where('current_month', $value)
                                            ->where('current_year', $year)
                                            ->exists();

                                        if ($exists) {
                                            $months = [
                                                1 => 'Janvier',
                                                2 => 'Février',
                                                3 => 'Mars',
                                                4 => 'Avril',
                                                5 => 'Mai',
                                                6 => 'Juin',
                                                7 => 'Juillet',
                                                8 => 'Août',
                                                9 => 'Septembre',
                                                10 => 'Octobre',
                                                11 => 'Novembre',
                                                12 => 'Décembre'
                                            ];
                                            $monthName = $months[$value] ?? $value;

                                            RemittanceValidationService::createWarningNotification(
                                                'Doublon détecté',
                                                "Un reversement de loyer existe déjà pour {$monthName} {$year} pour cette propriété.",
                                                [
                                                    'Vérifier l\'historique des reversements pour cette période',
                                                    'Sélectionner un autre mois si nécessaire',
                                                    'Consulter le statut du reversement existant'
                                                ]
                                            )->send();
                                        }
                                    }
                                }
                            };
                        }
                    ])
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        // Recalculer quand le mois change
                        $remittanceType = $get('remittance_type');
                        $ownerId = $get('owner_id');

                        if ($remittanceType === 'loyer' && $ownerId) {
                            self::calculateRemittanceAmount($remittanceType, $ownerId, null, $set, $get);
                        }
                    }),

                Select::make('current_year')
                    ->label('Année concernée')
                    ->options(collect(range(2020, 2030))->mapWithKeys(fn($year) => [$year => $year]))
                    ->required()
                    ->visible(fn(callable $get) => $get('remittance_type') === 'loyer')
                    ->default(now()->year)
                    ->dehydrated(true)
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $remittanceType = $get('remittance_type');
                        $ownerId = $get('owner_id');

                        if ($remittanceType === 'loyer' && $ownerId) {
                            self::calculateRemittanceAmount($remittanceType, $ownerId, null, $set, $get);
                        }
                    }),

                Select::make('owner_id')
                    ->label('Propriétaire')
                    ->options(function () {
                        return Owner::all()
                            ->filter(function ($owner) {
                                return $owner->hasValidPropertyForRemittance();
                            })
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->reactive()
                    ->required()
                    ->rules([
                        'required',
                        'exists:owners,id',
                        function () {
                            return function (string $attribute, $value, Closure $fail) {
                                if (!$value) {
                                    $fail('Un propriétaire doit être sélectionné.');
                                    return;
                                }

                                $owner = Owner::find($value);
                                if (!$owner) {
                                    $fail('Le propriétaire sélectionné n\'existe pas.');
                                    return;
                                }

                                // Validate owner has valid property for remittance
                                if (!$owner->hasValidPropertyForRemittance()) {
                                    $validationErrors = $owner->getRemittanceValidationErrors();
                                    $errorMessage = 'Le propriétaire sélectionné ne peut pas créer de reversement: ' . implode(', ', $validationErrors);
                                    $fail($errorMessage);
                                    return;
                                }

                                // Additional validation for property_id
                                $property = $owner->property();
                                if (!$property || !$property->id) {
                                    $fail('Le propriétaire sélectionné n\'a pas de propriété valide associée.');
                                    return;
                                }
                            };
                        }
                    ])
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        // Early validation before processing
                        if (!$state) {
                            // Reset all fields if no owner selected
                            $set('property_id', null);
                            $set('commission', 0);
                            $set('expenses', 0);
                            $set('amount_to_transfer', 0);
                            $set('amount', 0);
                            $set('remaining', 0);
                            $set('status', 'Pending');
                            $set('current_month', now()->month);
                            $set('current_year', now()->year);
                            return;
                        }

                        $owner = Owner::find($state);
                        if (!$owner) {
                            RemittanceValidationService::createErrorNotification(
                                'Erreur de validation',
                                'Le propriétaire sélectionné n\'existe pas.',
                                [
                                    'Vérifier que le propriétaire n\'a pas été supprimé',
                                    'Actualiser la page et réessayer',
                                    'Contacter l\'administrateur si le problème persiste'
                                ]
                            )->send();
                            return;
                        }

                        // Validate owner can create remittance
                        if (!$owner->hasValidPropertyForRemittance()) {
                            RemittanceValidationService::notifyOwnerValidationFailure($owner);

                            // Reset fields
                            $set('property_id', null);
                            $set('commission', 0);
                            $set('expenses', 0);
                            $set('amount_to_transfer', 0);
                            $set('amount', 0);
                            $set('remaining', 0);
                            return;
                        }

                        // Get property and validate it exists
                        $property = $owner->property();
                        if (!$property || !$property->id) {
                            RemittanceValidationService::createErrorNotification(
                                'Erreur de propriété',
                                'Le propriétaire sélectionné n\'a pas de propriété valide associée.',
                                [
                                    'Associer une propriété à ce propriétaire',
                                    'Vérifier la configuration du propriétaire',
                                    'Contacter l\'administrateur système'
                                ]
                            )->send();
                            return;
                        }

                        // Set property_id
                        $set('property_id', $property->id);

                        // Calculate remittance amount if type is selected
                        $remittanceType = $get('remittance_type');
                        if ($remittanceType) {
                            self::calculateRemittanceAmount($remittanceType, $state, $get('tenant_id'), $set, $get);
                        }
                    }),

                Select::make('tenant_id')
                    ->label('Locataire')
                    ->options(
                        fn(callable $get) =>
                        Tenant::whereHas('contracts', function ($query) use ($get) {
                            $query->where('property_id', $get('property_id'))
                                ->where('status', 'active');
                        })->pluck('name', 'id')
                    )
                    ->searchable()
                    ->reactive()
                    ->required()
                    ->visible(fn(callable $get) => $get('remittance_type') === 'caution')
                    ->rule(function ($get) {
                        return function (string $attribute, $value, Closure $fail) use ($get) {
                            $remittanceType = $get('remittance_type');
                            $propertyId = $get('property_id');

                            if ($remittanceType === 'caution' && $propertyId && $value) {
                                // Vérifier s’il y a un reversement de caution pour ce locataire via la property
                                $remittanceExists = Remittance::where('property_id', $propertyId)
                                    ->where('remittance_type', 'caution')
                                    ->whereHas('property.flats.contracts.tenant', function ($query) use ($value) {
                                        $query->where('id', $value); // $value = tenant_id sélectionné
                                    })
                                    ->exists();

                                if ($remittanceExists) {
                                    // Un reversement de caution existe déjà pour ce locataire
                                    RemittanceValidationService::createWarningNotification(
                                        'Reversement de caution existant',
                                        'Un reversement de caution existe déjà pour ce locataire dans cette propriété.',
                                        [
                                            'Vérifier l\'historique des reversements de ce locataire',
                                            'Consulter le statut du reversement existant',
                                            'Sélectionner un autre locataire si nécessaire'
                                        ]
                                    )->send();
                                    $fail('Un reversement de caution existe déjà pour ce locataire dans ce bien.');
                                }
                            }
                        };
                    })

                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $remittanceType = $get('remittance_type');
                        $ownerId = $get('owner_id');

                        if ($remittanceType && $ownerId) {
                            self::calculateRemittanceAmount($remittanceType, $ownerId, $state, $set, $get);
                        }
                    }),

                Select::make('property_id')
                    ->label('Propriété')
                    ->relationship('property', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->rules([
                        'required',
                        'exists:properties,id',
                        function () {
                            return function (string $attribute, $value, Closure $fail) {
                                if (!$value) {
                                    $fail('Une propriété doit être sélectionnée.');
                                    return;
                                }

                                // Validate property exists and has required data
                                $property = Property::find($value);
                                if (!$property) {
                                    $fail('La propriété sélectionnée n\'existe pas.');
                                    return;
                                }

                                // Validate property has agency association
                                if (!$property->agency_id) {
                                    $fail('La propriété sélectionnée n\'est pas associée à une agence.');
                                    return;
                                }

                                // Validate property has flats for payment calculations
                                if ($property->flats()->count() === 0) {
                                    $fail('La propriété sélectionnée n\'a pas d\'appartements associés pour les calculs de paiement.');
                                    return;
                                }

                                // Validate commission settings exist
                                if (!$property->commission_value) {
                                    $fail('La propriété sélectionnée n\'a pas de valeur de commission configurée.');
                                    return;
                                }

                                if (!$property->commission_unit) {
                                    $fail('La propriété sélectionnée n\'a pas d\'unité de commission configurée.');
                                    return;
                                }
                            };
                        }
                    ])
                    ->disabled()
                    ->dehydrated(true),

                TextInput::make('commission')
                    ->label('Commission')
                    ->disabled()
                    ->dehydrated(true),

                TextInput::make('expenses')
                    ->label('Dépenses')
                    ->disabled()
                    ->dehydrated(true),

                TextInput::make('amount_to_transfer')
                    ->label('Montant dû')
                    ->disabled()
                    ->dehydrated(true),

                TextInput::make('amount')
                    ->label('Montant versé')
                    ->default(0)
                    ->disabled()
                    ->dehydrated(true),

                TextInput::make('remaining')
                    ->label('Reste à reverser')
                    ->disabled()
                    ->dehydrated(true),

                Select::make('status')
                    ->label('Statut')
                    ->options([
                        'Pending' => 'En attente',
                        'Partial' => 'Partiel',
                        'Paid' => 'Complet',
                    ])
                    ->disabled()
                    ->dehydrated(true),



                DatePicker::make('remittance_date')
                    ->label('Date de création du reversement')
                    ->default(now())
                    ->required(),
            ]);
    }

    /**
     * Méthode privée pour calculer le montant du reversement
     */
    private static function calculateRemittanceAmount($remittanceType, $ownerId, $tenantId, callable $set, callable $get)
    {
        // Early validation - check if ownerId is provided
        if (!$ownerId) {
            RemittanceValidationService::createErrorNotification(
                'Erreur de calcul',
                'Aucun propriétaire sélectionné pour le calcul du reversement.',
                [
                    'Sélectionner un propriétaire valide',
                    'Vérifier que le formulaire est correctement rempli',
                    'Actualiser la page si nécessaire'
                ]
            )->send();
            return;
        }

        // Find and validate owner
        $owner = Owner::find($ownerId);
        if (!$owner) {
            RemittanceValidationService::createErrorNotification(
                'Erreur de calcul',
                'Le propriétaire sélectionné n\'existe pas.',
                [
                    'Vérifier que le propriétaire n\'a pas été supprimé',
                    'Sélectionner un autre propriétaire',
                    'Contacter l\'administrateur si le problème persiste'
                ]
            )->send();
            return;
        }

        // Validate owner can create remittance
        if (!$owner->hasValidPropertyForRemittance()) {
            RemittanceValidationService::notifyOwnerValidationFailure($owner);
            return;
        }

        // Get property and validate it exists
        $property = $owner->property();
        if (!$property || !$property->id) {
            RemittanceValidationService::createErrorNotification(
                'Erreur de propriété',
                'Le propriétaire n\'a pas de propriété valide associée.',
                [
                    'Associer une propriété à ce propriétaire',
                    'Vérifier la configuration du propriétaire',
                    'Contacter l\'administrateur système'
                ]
            )->send();
            return;
        }

        $propertyId = $property->id;

        // Validate property_id is not null before service calls
        if ($propertyId === null) {
            RemittanceValidationService::createErrorNotification(
                'Erreur de propriété',
                'L\'ID de la propriété est null, impossible de calculer le reversement.',
                [
                    'Vérifier l\'intégrité des données de la propriété',
                    'Contacter l\'administrateur système',
                    'Actualiser la page et réessayer'
                ]
            )->send();

            // Reset form fields to prevent invalid state
            $set('commission', 0);
            $set('expenses', 0);
            $set('amount_to_transfer', 0);
            $set('remaining', 0);
            $set('amount', 0);
            $set('status', 'Pending');
            return;
        }

        $paymentService = app(PaymentService::class);
        $month = $get('current_month') ?: now()->month;
        $year = $get('current_year') ?: now()->year;

        // S'assurer que month et year sont des entiers
        $month = (int) $month;
        $year = (int) $year;

        try {
            if ($remittanceType === 'loyer') {
                // Additional validation for rent calculations
                if ($month < 1 || $month > 12) {
                    throw new \InvalidArgumentException('Mois invalide pour le calcul du loyer');
                }

                if ($year < 2020 || $year > 2030) {
                    throw new \InvalidArgumentException('Année invalide pour le calcul du loyer');
                }

                // Calculer pour les loyers - with null check
                $amount = $paymentService->calculateLoyerTransferAmount($propertyId, $month, $year);
                $stats = $paymentService->getPropertyFinancialStats($propertyId, $month, $year);

                $set('commission', $stats['total_commissions'] ?? 0);
                $set('expenses', $stats['total_expenses'] ?? 0);
                $set('amount_to_transfer', $amount);
                $set('remaining', $amount);
            } elseif ($remittanceType === 'caution' && $tenantId) {
                // Validate tenant exists before calculation
                $tenant = Tenant::find($tenantId);
                if (!$tenant) {
                    throw new \InvalidArgumentException('Le locataire sélectionné n\'existe pas');
                }

                // Calculer pour les cautions - with null checks
                $amount = $paymentService->getTenantCautionAmount($tenantId, $propertyId);

                $set('commission', 0);
                $set('expenses', 0);
                $set('amount_to_transfer', $amount);
                $set('remaining', $amount);
            } elseif ($remittanceType === 'caution' && !$tenantId) {
                // Caution sans locataire sélectionné
                $set('commission', 0);
                $set('expenses', 0);
                $set('amount_to_transfer', 0);
                $set('remaining', 0);
            } else {
                // Invalid remittance type
                throw new \InvalidArgumentException('Type de reversement invalide: ' . $remittanceType);
            }

            $set('amount', 0);
            $set('status', 'Pending');
            $set('current_month', $month);
            $set('current_year', $year);

            // Success notification for successful calculation
            RemittanceValidationService::notifyCalculationSuccess($remittanceType, $amount ?? 0);
        } catch (\InvalidArgumentException $e) {
            // Handle validation errors with specific messages
            RemittanceValidationService::notifyCalculationFailure($remittanceType, [
                'month' => $month,
                'year' => $year,
                'error' => $e->getMessage()
            ]);

            // Reset form fields
            $set('commission', 0);
            $set('expenses', 0);
            $set('amount_to_transfer', 0);
            $set('remaining', 0);
            $set('amount', 0);
            $set('status', 'Pending');
        } catch (\Exception $e) {
            // Handle general errors
            RemittanceValidationService::createErrorNotification(
                'Erreur de calcul',
                'Une erreur inattendue s\'est produite lors du calcul du reversement.',
                [
                    'Vérifier les données saisies',
                    'Contacter l\'administrateur système si le problème persiste',
                    'Consulter les logs pour plus de détails'
                ]
            )->send();

            // Log the error for debugging
            \Log::error('RemittanceResource calculateRemittanceAmount error', [
                'remittanceType' => $remittanceType,
                'ownerId' => $ownerId,
                'tenantId' => $tenantId,
                'propertyId' => $propertyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // En cas d'erreur, réinitialiser les valeurs
            $set('commission', 0);
            $set('expenses', 0);
            $set('amount_to_transfer', 0);
            $set('remaining', 0);
            $set('amount', 0);
            $set('status', 'Pending');
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('remittance_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn($state) => match ($state?->value ?? $state) {
                        'loyer' => 'success',
                        'caution' => 'info',
                        default => 'gray',
                    })
                    ->icon(fn($state) => match ($state?->value ?? $state) {
                        'loyer' => 'heroicon-o-home',
                        'caution' => 'heroicon-o-shield-check',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->formatStateUsing(fn($state) => match ($state?->value ?? $state) {
                        'loyer' => 'Loyer',
                        'caution' => 'Caution',
                        default => $state?->value ?? $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('numero')
                    ->label('Numéro')
                    ->alignCenter()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Paid' => 'success',
                        'Partial' => 'warning',
                        'Pending' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'Paid' => 'heroicon-o-check-circle',
                        'Partial' => 'heroicon-o-clock',
                        'Pending' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'Paid' => 'Complet',
                        'Partial' => 'Partiel',
                        'Pending' => 'En attente',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Propriétaire')
                    ->getStateUsing(fn($record) => $record->owner?->name ?? 'Introuvable')
                    ->searchable()
                    ->sortable(),


                Tables\Columns\TextColumn::make('property.name')
                    ->label('Propriété')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Locataire')
                    ->getStateUsing(function ($record) {
                        $remittanceType = $record->remittance_type?->value ?? $record->remittance_type;

                        // Si c'est une caution et qu'un tenant_id est défini
                        if ($remittanceType === 'caution' && $record->property_id) {
                            $tenant = Tenant::whereHas('contracts', function ($query) use ($record) {
                                $query->where('property_id', $record->property_id)
                                    ->where('status', 'active');
                            })->first();
                            return $tenant ? $tenant->name : 'Locataire introuvable';
                        }

                        return 'N/A';
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('current_month')
                    ->label('Période')
                    ->getStateUsing(function ($record) {
                        if ($record->remittance_type?->value === 'caution' || $record->remittance_type === 'caution') {
                            return 'N/A';
                        }

                        $months = [
                            1 => 'Janvier',
                            2 => 'Février',
                            3 => 'Mars',
                            4 => 'Avril',
                            5 => 'Mai',
                            6 => 'Juin',
                            7 => 'Juillet',
                            8 => 'Août',
                            9 => 'Septembre',
                            10 => 'Octobre',
                            11 => 'Novembre',
                            12 => 'Décembre'
                        ];

                        return ($months[$record->current_month] ?? '') . ' ' . $record->current_year;
                    })
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('commission')
                    ->label('Commission')
                    ->money('XOF', locale: 'fr')
                    ->alignRight()
                    ->visible(fn($record) => $record?->remittance_type?->value === 'loyer' || $record?->remittance_type === 'loyer')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expenses')
                    ->label('Dépenses')
                    ->money('XOF', locale: 'fr')
                    ->alignRight()
                    ->visible(fn($record) => $record?->remittance_type?->value === 'loyer' || $record?->remittance_type === 'loyer')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_to_transfer')
                    ->label('Montant dû (brut)')
                    ->getStateUsing(function ($record) {
                        $remittanceType = $record->remittance_type?->value ?? $record->remittance_type;
                        $paymentService = app(PaymentService::class);

                        try {
                            if ($remittanceType === 'loyer' && $record->property_id && $record->current_month && $record->current_year) {
                                $stats = $paymentService->calculateAmountToTransferForProperty(
                                    $record->property_id,
                                    (int) $record->current_month,
                                    (int) $record->current_year
                                );
                                return $stats['total_loyers'] ?? 0;
                            }

                            if ($remittanceType === 'caution' && $record->tenant_id) {
                                return $paymentService->getTenantCautionAmount(
                                    $record->tenant_id,
                                    $record->property_id
                                );
                            }
                        } catch (\Exception $e) {
                            // \Log::error('Erreur calcul brut montant dû : ' . $e->getMessage());
                        }

                        return $record->amount_to_transfer ?? 0; // fallback
                    })
                    ->money('XOF', locale: 'fr')
                    ->color('info')
                    ->weight('bold')
                    ->alignRight()
                    ->sortable(),


                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant versé')
                    ->money('XOF', locale: 'fr')
                    ->color('success')
                    ->alignRight()
                    ->sortable(),

                Tables\Columns\TextColumn::make('remaining')
                    ->label('Reste à verser')
                    ->getStateUsing(function ($record) {
                        // Calculer le reste à verser dynamiquement
                        $amountToTransfer = $record->amount_to_transfer ?? 0;

                        // Si on peut recalculer le montant dû, on le fait
                        if ($record->property_id) {
                            $remittanceType = $record->remittance_type?->value ?? $record->remittance_type;
                            $paymentService = app(PaymentService::class);

                            try {
                                if ($remittanceType === 'loyer' && $record->current_month && $record->current_year) {
                                    $amountToTransfer = $paymentService->calculateLoyerTransferAmount(
                                        $record->property_id,
                                        (int) $record->current_month,
                                        (int) $record->current_year
                                    );
                                } elseif ($remittanceType === 'caution' && $record->tenant_id) {
                                    $amountToTransfer = $paymentService->getTenantCautionAmount(
                                        $record->tenant_id,
                                        $record->property_id
                                    );
                                }
                            } catch (\Exception $e) {
                                // Utiliser la valeur enregistrée en cas d'erreur
                                $amountToTransfer = $record->amount_to_transfer ?? 0;
                            }
                        }

                        return $amountToTransfer - ($record->amount ?? 0);
                    })
                    ->money('XOF', locale: 'fr')
                    ->color(fn($state) => $state > 0 ? 'danger' : 'success')
                    ->weight('bold')
                    ->alignRight()
                    ->sortable(),



                Tables\Columns\TextColumn::make('remittance_date')
                    ->label('Date de création')
                    ->date('d/m/Y')
                    ->alignCenter()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('remittance_type')
                    ->label('Type de reversement')
                    ->options([
                        'loyer' => 'Loyer',
                        'caution' => 'Caution',
                    ])
                    ->placeholder('Tous les types'),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'Pending' => 'En attente',
                        'Partial' => 'Partiel',
                        'Paid' => 'Complet',
                    ])
                    ->placeholder('Tous les statuts'),

                Tables\Filters\SelectFilter::make('owner_id')
                    ->label('Propriétaire')
                    ->options(function () {
                        return Owner::all()
                            ->filter(function ($owner) {
                                return $owner->hasValidPropertyForRemittance();
                            })
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->placeholder('Tous les propriétaires'),

                Tables\Filters\Filter::make('current_month')
                    ->form([
                        Forms\Components\Select::make('month')
                            ->label('Mois')
                            ->options([
                                1 => 'Janvier',
                                2 => 'Février',
                                3 => 'Mars',
                                4 => 'Avril',
                                5 => 'Mai',
                                6 => 'Juin',
                                7 => 'Juillet',
                                8 => 'Août',
                                9 => 'Septembre',
                                10 => 'Octobre',
                                11 => 'Novembre',
                                12 => 'Décembre'
                            ])
                            ->placeholder('Sélectionner un mois'),

                        Forms\Components\TextInput::make('year')
                            ->label('Année')
                            ->numeric()
                            ->placeholder(date('Y'))
                            ->minValue(2020)
                            ->maxValue(2030),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['month'],
                                fn(Builder $query, $month): Builder => $query->where('current_month', $month),
                            )
                            ->when(
                                $data['year'],
                                fn(Builder $query, $year): Builder => $query->where('current_year', $year),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['month'] ?? null) {
                            $months = [
                                1 => 'Janvier',
                                2 => 'Février',
                                3 => 'Mars',
                                4 => 'Avril',
                                5 => 'Mai',
                                6 => 'Juin',
                                7 => 'Juillet',
                                8 => 'Août',
                                9 => 'Septembre',
                                10 => 'Octobre',
                                11 => 'Novembre',
                                12 => 'Décembre'
                            ];
                            $indicators[] = 'Mois: ' . $months[$data['month']];
                        }

                        if ($data['year'] ?? null) {
                            $indicators[] = 'Année: ' . $data['year'];
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('remittance_date', 'desc');
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

    // Scoping personnalisé pour filtrer par agence via property
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        if ($tenant = \Filament\Facades\Filament::getTenant()) {
            // Debug: Log pour voir si cette méthode est appelée
            \Log::info('RemittanceResource getEloquentQuery called with tenant: ' . $tenant->name . ' (ID: ' . $tenant->id . ')');

            $query->whereHas('property', function ($subQuery) use ($tenant) {
                $subQuery->where('agency_id', $tenant->id);
            });
        } else {
            \Log::info('RemittanceResource getEloquentQuery called but no tenant found');
        }

        return $query;
    }
}
