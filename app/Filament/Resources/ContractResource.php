<?php

namespace App\Filament\Resources;

use App\Enums\ContractStatus;
use App\Enums\PropertyType;
use App\Filament\Resources\ContractResource\Pages;
use App\Filament\Resources\ContractResource\RelationManagers;
use App\Models\Contract;
use App\Models\Flat;
use App\Models\Tenant;
use App\Services\ContractService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Exception;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Collection;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContractResource extends Resource
{
   protected static ?string $model = Contract::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Contrats';
    protected static ?string $modelLabel = 'Contrat';
    protected static ?string $pluralModelLabel = 'Contrats';
    protected static ?int $navigationSort = 3;
   public static function form(Form $form): Form
{
    return $form
        ->schema([
            // 🔹 Informations Contrat
            Forms\Components\Section::make('Informations du Contrat')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Select::make('tenant_id')
                                ->label('Locataire')
                                ->relationship('tenant', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')->label('Nom complet')->required(),
                                    Forms\Components\TextInput::make('phone')->label('Téléphone')->tel(),
                                    Forms\Components\TextInput::make('address')->label('Adresse')->required(),
                                ])
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if ($state) {
                                        $set('flat_id', null);
                                        $set('monthly_rent', null);
                                        $set('cautions', null);

                                        $activeContract = Contract::where('tenant_id', $state)
                                            ->where('status', ContractStatus::ACTIVE)
                                            ->first();

                                        if ($activeContract) {
                                            Notification::make()
                                                ->title('Attention')
                                                ->body("Ce locataire a déjà un contrat actif du {$activeContract->start_date->format('d/m/Y')} au {$activeContract->end_date->format('d/m/Y')} dans l'appartement {$activeContract->flatThroughContract?->type->label()}.")
                                                ->warning()
                                                ->send();
                                        }
                                    }
                                }),
                        ]),

                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('contract_number')
                                ->label('N° Contrat')
                                ->disabled()
                                ->dehydrated(false)
                                ->placeholder('Généré automatiquement'),

                            Forms\Components\Select::make('status')
                                ->label('Statut')
                                ->options(ContractStatus::options())
                                ->default(ContractStatus::DRAFT->value)
                                ->required()
                                ->live(),
                        ]),
                ]),
            // 🔹 Sélection Propriété + Appartements disponibles
            Forms\Components\Section::make('Sélection du Bien')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Select::make('property_id')
                                ->label('Propriété')
                                ->relationship('property', 'name')
                                // ->options(PropertyType::getOptions())
                                ->searchable()
                                ->preload()
                                ->live()
                                ->required()
                                ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('flat_id', null)),

                            Forms\Components\Select::make('flat_id')
                                ->label('Appartement')
                                ->options(function (Forms\Get $get) {
                                    $propertyId = $get('property_id');

                                    if (!$propertyId) {
                                        return [];
                                    }

                                    return Flat::where('property_id', $propertyId)
                                        ->whereDoesntHave('contracts', function ($q) {
                                            $q->where('status', ContractStatus::ACTIVE);
                                        })
                                        ->get()
                                       ->mapWithKeys(function ($flat) {
                                            $label = $flat->type->label(); 

                                            if ($flat->monthly_rent) {
                                                $label .= ' - ' . number_format($flat->monthly_rent) . ' FCFA';
                                            }

                                            return [$flat->id => $label];
                                        });
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->disabled(fn (Forms\Get $get) => !$get('property_id'))
                                ->placeholder(function (Forms\Get $get) {
                                    $propertyId = $get('property_id');

                                    if (!$propertyId) {
                                        return "Sélectionnez d'abord une propriété";
                                    }

                                    $hasAvailable = Flat::where('property_id', $propertyId)
                                        ->whereDoesntHave('contracts', fn ($q) => $q->where('status', ContractStatus::ACTIVE))
                                        ->exists();

                                    return $hasAvailable
                                        ? 'Sélectionnez un appartement'
                                        : 'Aucun appartement disponible pour cette propriété';
                                })

                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if ($state) {
                                        $contractService = app(ContractService::class);
                                        $flatInfo = $contractService->getFlatInfo($state);

                                        $set('monthly_rent', $flatInfo['monthly_rent'] ?? null);
                                        $set('cautions', $flatInfo['cautions'] ?? null);
                                        $set('designation', $flatInfo['designation'] ?? null);
                                        $set('address', $flatInfo['address'] ?? null);
                                    } else {
                                        $set('monthly_rent', null);
                                        $set('cautions', null);
                                        $set('designation', null);
                                        $set('address', null);
                                    }
                                }),
                        ]),
                ]),

            

            // 🔹 Conditions Financières
            Forms\Components\Section::make('Conditions Financières')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('monthly_rent')
                                ->label('Loyer Mensuel')
                                ->numeric()
                                ->disabled()
                                ->dehydrated()
                                ->prefix('FCFA')
                                ->required(),

                            Forms\Components\TextInput::make('cautions')
                                ->label('Dépôt de Garantie')
                                ->numeric()
                                ->prefix('FCFA'),
                            Forms\Components\TextInput::make('designation')
                                ->label('Désignation de l\'appartement')
                                ->disabled()
                                ->dehydrated(),
                                
                            Forms\Components\TextInput::make('address')
                                ->label('Adresse de l\'appartement')
                                ->disabled()
                                ->dehydrated(),
                                
                        ]),
                ]),

            Forms\Components\Section::make('Période du Contrat')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Date de Début')
                                    ->required()
                                    ->default(Carbon::today())
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        static::calculateEndDate($state, $get('contract_duration_months'), $set);
                                    }),

                                Forms\Components\TextInput::make('contract_duration_months')
                                    ->label('Durée du contrat (mois)')
                                    ->numeric()
                                    ->default(12)
                                    ->minValue(1)
                                    ->maxValue(120) // 10 ans max
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        static::calculateEndDate($get('start_date'), $state, $set);
                                    })
                                    ->helperText('Entrez la durée en mois (ex: 12 pour 1 an)'),

                                Forms\Components\DatePicker::make('end_date')
                                    ->label('Date de Fin')
                                    ->disabled() // Calculée automatiquement
                                    ->dehydrated() // Mais sauvegardée en base
                                    ->required()
                                    ->helperText('Calculée automatiquement selon la durée'),
                            ]),
                    ]),

            // 🔹 Conditions du contrat
            Forms\Components\Section::make('Conditions du Contrat')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('notice_period_days')
                                ->label('Préavis (jours)')
                                ->numeric()
                                ->default(30)
                                ->required(),

                            Forms\Components\TextInput::make('alert_days_before')
                                ->label('Alerte avant expiration (jours)')
                                ->numeric()
                                ->default(90)
                                ->required(),
                        ]),
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Toggle::make('auto_renewal')
                                ->label('Renouvellement Automatique')
                                ->live(),

                            Forms\Components\TextInput::make('renewal_duration_months')
                                ->label('Durée renouvellement (mois)')
                                ->numeric()
                                ->default(12)
                                ->visible(fn (Forms\Get $get) => $get('auto_renewal')),
                        ]),
                ]),

            // 🔹 Notes
            Forms\Components\Section::make('Notes')
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label('Notes et Remarques')
                        ->rows(3),
                ])
                ->collapsible(),
                ]);
               
    
}

public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('contract_number')
                    ->label('N° Contrat')
                    ->searchable()
                    ->alignCenter()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('flat.property.name')
                    ->label('Propriété')
                    ->searchable()
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('flat.type')
                    ->label('Type d\'Appartement')
                    ->searchable()
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Locataire')
                    ->searchable()
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('monthly_rent')
                    ->label('Loyer')
                    ->alignCenter()
                    ->money('XOF')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->alignCenter()
                    ->colors(ContractStatus::colors())
                    ->icons(ContractStatus::icons())
                    ->formatStateUsing(fn ($state) => $state->label()),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Début')
                    ->alignCenter()
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->alignCenter()
                    ->sortable()
                    ->color(fn ($record) => $record->is_expiring_soon ? 'warning' : null),

                Tables\Columns\TextColumn::make('days_until_expiration')
                    ->label('Jours restants')
                    ->state(function ($record) {
                        if ($record->status !== ContractStatus::ACTIVE) return '-';
                        $days = round($record->days_until_expiration);
                        return $days > 0 ? $days . ' jours' : 'Expiré';
                    })
                    ->color(function ($record) {
                        if ($record->status !== ContractStatus::ACTIVE) return null;
                        $days = round($record->days_until_expiration);
                        if ($days < 0) return 'danger';
                        if ($days <= 30) return 'warning';
                        return 'success';
                    }),
            ])
          ->filters([  
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(ContractStatus::options()),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Expire bientôt')
                    ->query(fn (Builder $query) => $query->expiringSoon()),

                Tables\Filters\Filter::make('expired')
                    ->label('Expirés')
                    ->query(fn (Builder $query) => $query->expired()),
            ])
            
            ->actions([
    Tables\Actions\ActionGroup::make([
        // PDF actions
        Tables\Actions\Action::make('download_pdf')
            ->label('Générer PDF')
            ->icon('heroicon-o-document-arrow-down')
            ->color('info')
            ->url(fn ($record) => route('contracts.pdf', $record))
            ->openUrlInNewTab(),

        Tables\Actions\Action::make('view_pdf')
            ->label('Voir PDF')
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->url(fn ($record) => route('contracts.pdf.view', $record))
            ->openUrlInNewTab(),

        // Edit
        Tables\Actions\EditAction::make(),

        // Renew
        Tables\Actions\Action::make('renew')
            ->label('Renouveler')
            ->icon('heroicon-o-arrow-path')
            ->color('success')
            ->visible(fn ($record) => $record->status->canBeRenewed())
            ->form([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('duration_months')
                            ->label('Durée (mois)')
                            ->numeric()
                            ->default(fn ($record) => $record->renewal_duration_months ?? 12)
                            ->required()
                            ->minValue(1)
                            ->maxValue(120),

                        Forms\Components\TextInput::make('new_rent')
                            ->label('Nouveau loyer')
                            ->numeric()
                            ->prefix('FCFA')
                            ->default(fn ($record) => $record->monthly_rent)
                            ->required(),
                    ]),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Date de début')
                            ->default(fn ($record) => $record->end_date->addDay())
                            ->required(),

                        Forms\Components\Toggle::make('notify_tenant')
                            ->label('Notifier le locataire')
                            ->default(true),
                    ]),
                Forms\Components\Textarea::make('notes')
                    ->label('Notes sur le renouvellement')
                    ->rows(3),
            ])
            ->action(function ($record, array $data) {
                $contractService = app(ContractService::class);

                $newContract = $contractService->renewContract(
                    $record,
                    $data['duration_months'],
                    $data['new_rent']
                );

                if ($data['start_date'] !== $record->end_date->addDay()->toDateString()) {
                    $newContract->update([
                        'start_date' => $data['start_date'],
                        'end_date' => Carbon::parse($data['start_date'])->addMonths($data['duration_months']),
                    ]);
                }

                if (!empty($data['notes'])) {
                    $newContract->update([
                        'notes' => ($newContract->notes ?? '') . "\n" . $data['notes'],
                    ]);
                }

                Notification::make()
                    ->title('Contrat renouvelé avec succès')
                    ->body("Nouveau contrat créé : {$newContract->contract_number}")
                    ->success()
                    ->send();
            }),

        // Terminate
        Tables\Actions\Action::make('terminate')
            ->label('Résilier')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn ($record) => $record->status->canBeTerminated())
            ->requiresConfirmation()
            ->form([
                Forms\Components\Textarea::make('reason')
                    ->label('Motif de résiliation')
                    ->required(),
                // date de résiliation
            ])
            ->action(function ($record, array $data) {
                $contractService = app(ContractService::class);
                $contractService->terminateContract($record, $data['reason']);

                Notification::make()
                    ->title('Contrat résilié')
                    ->success()
                    ->send();
            }),
    ])
    ->label('Actions')
    ->icon('heroicon-o-ellipsis-vertical'),
])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulk_renew')
                        ->label('Renouveler en masse')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->form([
                            Forms\Components\TextInput::make('duration_months')
                                ->label('Durée (mois)')
                                ->numeric()
                                ->default(12)
                                ->required(),
        
        Forms\Components\Toggle::make('keep_same_rent')
            ->label('Conserver le même loyer')
            ->default(true),
            
        Forms\Components\TextInput::make('rent_increase_percentage')
            ->label('Augmentation du loyer (%)')
            ->numeric()
            ->visible(fn (Forms\Get $get) => !$get('keep_same_rent')),
        ])
        ->action(function (Collection $records, array $data) {
            $contractService = app(ContractService::class);
            $renewed = 0;
            
            foreach ($records as $contract) {
                if ($contract->status->canBeRenewed()) {
                    $newRent = $data['keep_same_rent'] 
                        ? null 
                        : $contract->monthly_rent * (1 + ($data['rent_increase_percentage'] / 100));
                        
                    $contractService->renewContract($contract, $data['duration_months'], $newRent);
                    $renewed++;
                }
            }
            
            Notification::make()
                ->title("$renewed contrats renouvelés avec succès")
                ->success()
                ->send();
        })
        ->deselectRecordsAfterCompletion()
        ->requiresConfirmation(),
                    ]),
                ])
                ->defaultSort('end_date', 'asc');
        }

    public static function getRelations(): array
    {
        return [
            //
            RelationManagers\PaymentRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContracts::route('/'),
            'create' => Pages\CreateContract::route('/create'),
            // 'view' => Pages\ViewContract::route('/{record}'),
            'edit' => Pages\EditContract::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::expiringSoon()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

/**
     * Calcule automatiquement la date de fin du contrat
     */
    private static function calculateEndDate($startDate, $durationMonths, Forms\Set $set): void
    {
        if ($startDate && $durationMonths) {
            try {
                $endDate = Carbon::parse($startDate)->addMonths((int) $durationMonths);
                $set('end_date', $endDate->format('Y-m-d'));
            } catch (Exception $e) {
                // En cas d'erreur, on ne fait rien pour éviter de casser le formulaire
                   \Illuminate\Support\Facades\Log::warning('Erreur calcul date fin contrat: ' . $e->getMessage());
            }
        }
    }

}
