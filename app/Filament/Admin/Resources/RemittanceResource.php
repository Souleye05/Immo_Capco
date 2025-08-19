<?php

namespace App\Filament\Admin\Resources;

use App\Enums\RemittanceType;
use App\Enums\RemittanceStatus;
use App\Filament\Admin\Resources\RemittanceResource\Pages;
use App\Models\Remittance;
use App\Models\Owner;
use App\Models\Tenant;
use App\Services\PaymentService;
use Coolsam\FilamentFlatpickr\Forms\Components\Flatpickr;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\{DatePicker, TextInput, Select, Section, Grid};
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class RemittanceResource extends Resource
{
    protected static ?string $model = Remittance::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Paiement';
    public static ?string $label = 'Reversement';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informations du reversement')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('remittance_type')
                                    ->label('Type de reversement')
                                    ->options([
                                        RemittanceType::LOYER->value => 'Loyer',
                                        RemittanceType::CAUTION->value => 'Caution',
                                    ])
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        self::resetFormFields($set);
                                        $ownerId = $get('owner_id');
                                        if ($ownerId && $state) {
                                            self::calculateRemittanceAmount($state, $ownerId, $get('tenant_id'), $set, $get);
                                        }
                                    }),

                                Select::make('owner_id')
                                    ->label('Propriétaire')
                                    ->options(Owner::pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        self::handleOwnerChange($state, $set, $get);
                                    }),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Flatpickr::make('current_month')
                                    ->label('Mois concerné')
                                    ->monthSelect()
                                    ->required()
                                    ->visible(fn(callable $get) => $get('remittance_type') === RemittanceType::LOYER->value)
                                    ->reactive()
                                    ->default(now()->month)
                                    ->rules([
                                        function (callable $get) {
                                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                $remittanceType = $get('remittance_type');
                                                $ownerId = $get('owner_id');
                                                $year = $get('current_year') ?? now()->year;

                                                if ($remittanceType === RemittanceType::LOYER->value && $ownerId && $value) {
                                                    $owner = Owner::find($ownerId);
                                                    if ($owner && $owner->property) {
                                                        $exists = Remittance::where('property_id', $owner->property->id)
                                                            ->where('remittance_type', RemittanceType::LOYER)
                                                            ->where('current_month', $value)
                                                            ->where('current_year', $year)
                                                            ->exists();

                                                        if ($exists) {
                                                            $months = [
                                                                1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
                                                                5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
                                                                9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
                                                            ];
                                                            $monthName = $months[$value] ?? $value;
                                                            $fail("Un reversement de loyer existe déjà pour {$monthName} {$year} pour cette propriété.");
                                                        }
                                                    }
                                                }
                                            };
                                        }
                                    ])
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $remittanceType = $get('remittance_type');
                                        $ownerId = $get('owner_id');
                                        if ($remittanceType && $ownerId) {
                                            self::calculateRemittanceAmount($remittanceType, $ownerId, $get('tenant_id'), $set, $get);
                                        }
                                    }),

                                TextInput::make('current_year')
                                    ->label('Année concernée')
                                    ->numeric()
                                    ->required()
                                    ->visible(fn(callable $get) => $get('remittance_type') === RemittanceType::LOYER->value)
                                    ->reactive()
                                    ->default(now()->year)
                                    ->minValue(2020)
                                    ->maxValue(now()->year + 1)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $remittanceType = $get('remittance_type');
                                        $ownerId = $get('owner_id');
                                        if ($remittanceType && $ownerId) {
                                            self::calculateRemittanceAmount($remittanceType, $ownerId, $get('tenant_id'), $set, $get);
                                        }
                                    }),

                                DatePicker::make('remittance_date')
                                    ->label('Date de création')
                                    ->default(now())
                                    ->required(),
                            ]),

                        Select::make('tenant_id')
                            ->label('Locataire')
                            ->options(fn(callable $get) => 
                                Tenant::whereHas('contracts', function ($query) use ($get) {
                                    $query->where('property_id', $get('property_id'))
                                        ->where('status', 'active');
                                })->pluck('name', 'id')
                            )
                            ->searchable()
                            ->visible(fn(callable $get) => $get('remittance_type') === RemittanceType::CAUTION->value)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $remittanceType = $get('remittance_type');
                                $ownerId = $get('owner_id');
                                if ($remittanceType && $ownerId) {
                                    self::calculateRemittanceAmount($remittanceType, $ownerId, $state, $set, $get);
                                }
                            }),
                    ]),

                Section::make('Détails financiers')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('numero')
                                    ->label('Numéro')
                                    ->required()
                                    ->unique(ignoreRecord: true),

                                Select::make('status')
                                    ->label('Statut')
                                    ->options([
                                        RemittanceStatus::PENDING->value => 'En attente',
                                        RemittanceStatus::PARTIAL->value => 'Partiel',
                                        RemittanceStatus::PAID->value => 'Complet',
                                    ])
                                    ->default(RemittanceStatus::PENDING->value)
                                    ->required(),
                            ]),

                        Grid::make(4)
                            ->schema([
                                TextInput::make('commission')
                                    ->label('Commission')
                                    ->numeric()
                                    ->disabled()
                                    ->suffix('F CFA'),

                                TextInput::make('expenses')
                                    ->label('Charges')
                                    ->numeric()
                                    ->disabled()
                                    ->suffix('F CFA'),

                                TextInput::make('amount_to_transfer')
                                    ->label('Montant à reverser')
                                    ->numeric()
                                    ->disabled()
                                    ->suffix('F CFA'),

                                TextInput::make('remaining')
                                    ->label('Restant')
                                    ->numeric()
                                    ->disabled()
                                    ->suffix('F CFA'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('amount')
                                    ->label('Montant versé')
                                    ->numeric()
                                    ->suffix('F CFA')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $amountToTransfer = $get('amount_to_transfer') ?? 0;
                                        $remaining = max(0, $amountToTransfer - ($state ?? 0));
                                        $set('remaining', $remaining);
                                        
                                        if ($remaining <= 0) {
                                            $set('status', RemittanceStatus::PAID->value);
                                        } elseif ($state > 0) {
                                            $set('status', RemittanceStatus::PARTIAL->value);
                                        } else {
                                            $set('status', RemittanceStatus::PENDING->value);
                                        }
                                    }),

                                Select::make('mode_remit')
                                    ->label('Mode de reversement')
                                    ->options([
                                        'OM' => 'Orange Money',
                                        'Wave' => 'Wave',
                                        'Free Money' => 'Free Money',
                                        'Chèque' => 'Chèque',
                                        'Virement' => 'Virement',
                                        'Espèces' => 'Espèces',
                                    ]),
                            ]),

                        TextInput::make('property_id')
                            ->hidden()
                            ->dehydrated(true),
                    ]),
            ]);
    }

    private static function handleOwnerChange($state, callable $set, callable $get): void
    {
        if (!$state) {
            self::resetFormFields($set);
            return;
        }

        $owner = Owner::find($state);
        if (!$owner) {
            self::notifyOwnerError();
            self::resetFormFields($set);
            return;
        }

        $property = $owner->property;
        if (!$property) {
            self::notifyPropertyError();
            self::resetFormFields($set);
            return;
        }

        $set('property_id', $property->id);

        // Set default month and year if not set
        if (!$get('current_month')) {
            $set('current_month', now()->month);
        }
        if (!$get('current_year')) {
            $set('current_year', now()->year);
        }

        // Calculate if type is selected
        $remittanceType = $get('remittance_type');
        if ($remittanceType) {
            self::calculateRemittanceAmount($remittanceType, $state, $get('tenant_id'), $set, $get);
        }
    }

    private static function calculateRemittanceAmount($remittanceType, $ownerId, $tenantId, callable $set, callable $get): void
    {
        try {
            $owner = Owner::find($ownerId);
            if (!$owner) {
                throw new \InvalidArgumentException('Propriétaire non trouvé');
            }

            $property = $owner->property;
            if (!$property) {
                throw new \InvalidArgumentException('Propriété non trouvée');
            }

            $paymentService = app(PaymentService::class);
            $month = (int) ($get('current_month') ?: now()->month);
            $year = (int) ($get('current_year') ?: now()->year);

            if ($remittanceType === RemittanceType::LOYER->value) {
                $amount = $paymentService->calculateLoyerTransferAmount($property->id, $month, $year);
                $stats = $paymentService->getPropertyFinancialStats($property->id, $month, $year);

                $set('commission', $stats['total_commissions'] ?? 0);
                $set('expenses', $stats['total_expenses'] ?? 0);
                $set('amount_to_transfer', $amount);
                $set('remaining', $amount);
            } elseif ($remittanceType === RemittanceType::CAUTION->value && $tenantId) {
                $amount = $paymentService->getTenantCautionAmount($tenantId, $property->id);
                $set('commission', 0);
                $set('expenses', 0);
                $set('amount_to_transfer', $amount);
                $set('remaining', $amount);
            } else {
                $set('commission', 0);
                $set('expenses', 0);
                $set('amount_to_transfer', 0);
                $set('remaining', 0);
            }

            $set('amount', 0);
            $set('status', RemittanceStatus::PENDING->value);

        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur de calcul')
                ->body($e->getMessage())
                ->danger()
                ->send();

            self::resetFormFields($set);
        }
    }

    private static function resetFormFields(callable $set): void
    {
        $set('property_id', null);
        $set('commission', 0);
        $set('expenses', 0);
        $set('amount_to_transfer', 0);
        $set('amount', 0);
        $set('remaining', 0);
        $set('status', RemittanceStatus::PENDING->value);
    }

    private static function notifyOwnerError(): void
    {
        Notification::make()
            ->title('Erreur')
            ->body('Ce propriétaire ne peut pas créer de remittance.')
            ->danger()
            ->send();
    }

    private static function notifyPropertyError(): void
    {
        Notification::make()
            ->title('Erreur')
            ->body('Aucune propriété trouvée pour ce propriétaire.')
            ->danger()
            ->send();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero')
                    ->label('Numéro')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Propriétaire')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('remittance_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        RemittanceType::LOYER->value => 'success',
                        RemittanceType::CAUTION->value => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        RemittanceType::LOYER->value => 'Loyer',
                        RemittanceType::CAUTION->value => 'Caution',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('current_month')
                    ->label('Période')
                    ->getStateUsing(function ($record) {
                        if ($record->remittance_type !== RemittanceType::LOYER) {
                            return '-';
                        }

                        $months = [
                            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
                            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
                            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
                        ];

                        return ($months[$record->current_month] ?? '') . ' ' . $record->current_year;
                    })
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_to_transfer')
                    ->label('Montant à reverser')
                    ->money('XOF')
                    ->alignRight()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        RemittanceStatus::PAID->value => 'success',
                        RemittanceStatus::PARTIAL->value => 'warning',
                        RemittanceStatus::PENDING->value => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        RemittanceStatus::PAID->value => 'Complet',
                        RemittanceStatus::PARTIAL->value => 'Partiel',
                        RemittanceStatus::PENDING->value => 'En attente',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('remittance_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('remittance_type')
                    ->label('Type')
                    ->options([
                        RemittanceType::LOYER->value => 'Loyer',
                        RemittanceType::CAUTION->value => 'Caution',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        RemittanceStatus::PENDING->value => 'En attente',
                        RemittanceStatus::PARTIAL->value => 'Partiel',
                        RemittanceStatus::PAID->value => 'Complet',
                    ]),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRemittances::route('/'),
            'create' => Pages\CreateRemittance::route('/create'),
            'edit' => Pages\EditRemittance::route('/{record}/edit'),
        ];
    }
}