<?php

namespace App\Filament\Admin\Resources;

use App\Enums\CommissionUnit;
use App\Enums\MonthEnum;
use App\Enums\PropertyType;
use App\Filament\Admin\Resources\PropertyResource\Pages;
use App\Filament\Admin\Resources\PropertyResource\RelationManagers;
use App\Models\Property;
use App\Repositories\ExpenseRepository;
use App\Services\PaymentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Tables\Table;
use App\Traits\HasAgencyPermissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

class PropertyResource extends Resource
{
    use HasAgencyPermissions;

    protected static ?string $model = Property::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationGroup = 'Gestion';

    public static ?string $label = 'Immeubles';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Type de propriété')
                            ->options(PropertyType::getOptions())
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),


                        Forms\Components\TextInput::make('address')
                            ->label('Adresse')
                            ->required()
                            ->maxLength(500),


                    ])
                    ->columns(2),

                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('commission_value')
                            ->label('Commission sur la propriété')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01),

                        Forms\Components\Select::make('commission_unit')
                            ->label('Unité de commission')
                            ->options(CommissionUnit::getOptions())
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('number_flat')
                            ->label("Nombre d'appartements dans la propriété")
                            ->numeric()
                            ->required()
                            ->minValue(1),
                    ])
                    ->columns(3),
            ]);
    }



    // public static function table(Table $table): Table
    // {
    //     return $table
    //         ->columns([
    //             Tables\Columns\TextColumn::make('type')
    //                 ->label('Type de propriété')
    //                 ->searchable()
    //                 ->alignCenter() 
    //                 ->sortable(),
    //             Tables\Columns\TextColumn::make('name')
    //                 ->label('Nom')
    //                 ->searchable()
    //                 ->alignCenter()
    //                 ->sortable(),
    //             Tables\Columns\TextColumn::make('address')
    //                 ->label('Adresse')
    //                 ->searchable()
    //                 ->alignCenter()
    //                 ->sortable(),
    //             Tables\Columns\TextColumn::make('commission_value')
    //                 ->label('Commission')
    //                 ->alignCenter()
    //                 ->suffix(fn (Property $record) => ' ' . $record->commission_unit)   
    //                 ->sortable(),
    //             Tables\Columns\TextColumn::make('number_flat')
    //                 ->label("Nombre d'appartements")
    //                 ->alignCenter()
    //                 ->sortable(),
    //                 // Nouvelle colonne pour les commissions mensuelles
    //             Tables\Columns\TextColumn::make('monthly_commissions')
    //             ->label('Commissions')
    //             ->alignCenter()
    //             ->formatStateUsing(fn (float $state): string => number_format($state, 0, ',', ' ') . ' F CFA')
    //             ->state(function (Property $record): float {
    //                 $paymentService = app(PaymentService::class);
    //                 // Récupérer les valeurs du filtre ou utiliser les valeurs par défaut
    //                 $currentMonth = request()->input('tableFilters.month_year.month') ?? Carbon::now()->month;
    //                 $currentYear = request()->input('tableFilters.month_year.year') ?? Carbon::now()->year;


    //                 return $paymentService->calculateMonthlyCommissionsForProperty(
    //                     $record->id, 
    //                     $currentMonth, 
    //                     $currentYear
    //                 );
    //             })
    //             ->sortable(),

    //             // Nouvelle colonne pour les dépenses mensuelles
    //             Tables\Columns\TextColumn::make('monthly_expenses')
    //                 ->label('Dépenses ')
    //                 ->alignCenter()
    //                 ->formatStateUsing(fn (float $state): string => number_format($state, 0, ',', ' ') . ' F CFA')
    //                 ->state(function (Property $record): float {
    //                     $expenseRepository = app(\App\Repositories\ExpenseRepository::class);
    //                     // Récupérer les valeurs du filtre ou utiliser les valeurs par défaut
    //                     $currentMonth = request()->input('tableFilters.month_year.month') ?? Carbon::now()->month;
    //                     $currentYear = request()->input('tableFilters.month_year.year') ?? Carbon::now()->year;


    //                     // Cette méthode retourne déjà le total des dépenses pour tous les appartements + dépenses spécifiques
    //                     return $expenseRepository->calculateMonthlyExpensesForProperty(
    //                         $record->id, 
    //                         $currentMonth, 
    //                         $currentYear
    //                     );
    //                 })
    //                 ->sortable(),
    //            // Nouvelle colonne pour le montant à reverser
    //            Tables\Columns\TextColumn::make('amount_to_transfer')
    //            ->label('Montant à reverser')
    //            ->alignCenter()
    //            ->formatStateUsing(fn (float $state): string => number_format($state, 0, ',', ' ') . ' F CFA')
    //            ->state(function (Property $record): float {
    //                $paymentService = app(PaymentService::class);
    //                // Récupérer les valeurs du filtre ou utiliser les valeurs par défaut
    //                 $currentMonth = request()->input('tableFilters.month_year.month') ?? Carbon::now()->month;
    //                 $currentYear = request()->input('tableFilters.month_year.year') ?? Carbon::now()->year;


    //                return $paymentService->calculateAmountToTransferForProperty(
    //                    $record->id, 
    //                    $currentMonth, 
    //                    $currentYear
    //                );
    //            })
    //            ->sortable()

    //            ->color(fn (float $state): string => $state > 0 ? 'success' : 'danger'),
    //             Tables\Columns\TextColumn::make('created_at')
    //                 ->label('Créé le')
    //                 ->alignCenter()
    //                 ->dateTime('d/m/Y H:i')
    //                 ->sortable()
    //                 ->toggleable(isToggledHiddenByDefault: true),
    //             Tables\Columns\TextColumn::make('updated_at')
    //                 ->label('Mis à jour le')
    //                 ->dateTime('d/m/Y H:i')
    //                 ->sortable()
    //                 ->toggleable(isToggledHiddenByDefault: true),
    //         ])
    //         ->filters([
    //             // Filtre par mois et année
    //             Tables\Filters\Filter::make('month_year')
    //                 ->form([
    //                     Forms\Components\Select::make('month')
    //                         ->label('Mois')
    //                         ->options([
    //                             1 => 'Janvier',
    //                             2 => 'Février',
    //                             3 => 'Mars',
    //                             4 => 'Avril',
    //                             5 => 'Mai',
    //                             6 => 'Juin',
    //                             7 => 'Juillet',
    //                             8 => 'Août',
    //                             9 => 'Septembre',
    //                             10 => 'Octobre',
    //                             11 => 'Novembre',
    //                             12 => 'Décembre',
    //                         ])
    //                         ->default(Carbon::now()->month),
    //                     Forms\Components\Select::make('year')
    //                         ->label('Année')
    //                         ->options(function () {
    //                             $years = [];
    //                             $currentYear = Carbon::now()->year;
    //                             for ($i = $currentYear - 2; $i <= $currentYear; $i++) {
    //                                 $years[$i] = $i;
    //                             }
    //                             return $years;
    //                         })
    //                         ->default(Carbon::now()->year),
    //                 ])
    //                 ->query(function (Builder $query, array $data): Builder {
    //                     // Ce filtre n'affecte pas directement la requête SQL
    //                     // Il est utilisé pour mettre à jour les colonnes de calcul financier
    //                     return $query;
    //                 })
    //                 ->indicateUsing(function (array $data): array {
    //                     $indicators = [];

    //                     if ($data['month'] ?? null) {
    //                         $monthName = [
    //                             1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
    //                             5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
    //                             9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
    //                         ][$data['month']];

    //                         $indicators['month'] = "Mois: {$monthName}";
    //                     }

    //                     if ($data['year'] ?? null) {
    //                         $indicators['year'] = "Année: {$data['year']}";
    //                     }

    //                     return $indicators;
    //                 }),
    //         ])
    //         ->actions([
    //             Tables\Actions\EditAction::make(),
    //             // Ajouter un bouton pour afficher les détails financiers
    //             Tables\Actions\Action::make('view_financials')
    //                 ->label('Détails financiers')
    //                 ->icon('heroicon-o-currency-dollar')
    //                 ->color('success')
    //                 ->action(function (Property $record) {
    //                     // Rediriger vers une page de détails financiers si nécessaire
    //                     // ou afficher un modal avec les détails
    //                 })
    //                 ->modalContent(function (Property $record) {
    //                     $paymentService = app(PaymentService::class);
    //                     $currentMonth = request()->input('tableFilters.month_year.month') ?? Carbon::now()->month;
    //                     $currentYear = request()->input('tableFilters.month_year.year') ?? Carbon::now()->year;

    //                     $stats = $paymentService->getPropertyFinancialStats(
    //                         $record->id, 
    //                         $currentMonth, 
    //                         $currentYear
    //                     );

    //                     return view('filament.resources.property-resource.components.financial-details', [
    //                         'stats' => $stats,
    //                         'property' => $record,
    //                         'month' => $currentMonth,
    //                         'year' => $currentYear
    //                     ]);
    //                 })
    //                 ->modalHeading(fn (Property $record) => "Détails financiers - {$record->name}")
    //                 ->modalWidth('xl'),
    //         ])
    //         ->bulkActions([
    //             Tables\Actions\BulkActionGroup::make([
    //                 Tables\Actions\DeleteBulkAction::make(),
    //             ]),
    //         ]);
    // }

    // public static function getRelations(): array
    // {
    //     return [
    //         //
    //         RelationManagers\FlatRelationManager::class,
    //     ];
    // }

    // public static function getPages(): array
    // {
    //     return [
    //         'index' => Pages\ListProperties::route('/'),
    //         'create' => Pages\CreateProperty::route('/create'),
    //         'edit' => Pages\EditProperty::route('/{record}/edit'),
    //     ];
    // }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn(PropertyType $state): string => $state->getColor())
                    ->icon(fn(PropertyType $state): string => $state->getIcon())
                    ->formatStateUsing(fn(PropertyType $state) => $state->getLabel())
                    ->searchable()
                    ->sortable(),


                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),


                Tables\Columns\TextColumn::make('address')
                    ->label('Adresse')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn($record) => $record->address),


                Tables\Columns\TextColumn::make('commission_display')
                    ->label('Commission')
                    ->state(
                        fn(Property $record) =>
                        $record->commission_value . ' ' . $record->commission_unit->getLabel()
                    )
                    ->sortable(
                        query: fn(Builder $query, string $direction) =>
                        $query->orderBy('commission_value', $direction)
                    ),

                Tables\Columns\TextColumn::make('number_flat')
                    ->label("Appartements")
                    ->alignCenter()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                self::getMonthlyCommissionsColumn(),
                self::getMonthlyExpensesColumn(),
                self::getAmountToTransferColumn(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                self::getMonthYearFilter(),
                self::getPropertyTypeFilter(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                self::getFinancialDetailsAction(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    private static function getPropertyTypeFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('type')
            ->label('Type de propriété')
            ->options(PropertyType::getOptions())
            ->multiple();
    }

    /*************  ✨ Windsurf Command ⭐  *************/
    /**
/*******  a99aacf1-345a-440e-9adc-556e0afab775  *******/
    private static function getMonthlyCommissionsColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('monthly_commissions')
            ->label('Commissions')
            ->alignCenter()
            ->formatStateUsing(
                fn(float $state): string =>
                self::formatCurrency($state)
            )
            ->state(function (Property $record): float {
                $paymentService = app(PaymentService::class);
                [$currentMonth, $currentYear] = self::getCurrentMonthYear();

                return $paymentService->calculateMonthlyCommissionsForProperty(
                    $record->id,
                    $currentMonth,
                    $currentYear
                );
            })
            ->sortable();
    }


    private static function getMonthlyExpensesColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('monthly_expenses')
            ->label('Dépenses')
            ->alignCenter()
            ->formatStateUsing(
                fn(float $state): string =>
                self::formatCurrency($state)
            )
            ->state(function (Property $record): float {
                $expenseRepository = app(ExpenseRepository::class);
                [$currentMonth, $currentYear] = self::getCurrentMonthYear();

                return $expenseRepository->calculateMonthlyExpensesForProperty(
                    $record->id,
                    $currentMonth,
                    $currentYear
                );
            })
            ->sortable();
    }
    private static function getAmountToTransferColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('amount_to_transfer')
            ->label('Montant à reverser')
            ->alignCenter()
            ->formatStateUsing(
                fn(float $state): string =>
                self::formatCurrency($state)
            )
            ->state(function (Property $record): float {
                $paymentService = app(PaymentService::class);
                [$currentMonth, $currentYear] = self::getCurrentMonthYear();

                return $paymentService->calculateAmountToTransferForProperty(
                    $record->id,
                    $currentMonth,
                    $currentYear
                );
            })
            ->sortable()
            ->color(fn(float $state): string => $state > 0 ? 'success' : 'danger')
            ->weight(fn(float $state): string => $state != 0 ? 'bold' : 'normal');
    }


    private static function getMonthYearFilter(): Tables\Filters\Filter
    {
        return Tables\Filters\Filter::make('month_year')
            ->form([
                Forms\Components\Select::make('month')
                    ->label('Mois')
                    ->options(MonthEnum::getOptions())
                    ->default(MonthEnum::getCurrentMonth()->value)
                    ->native(false),

                Forms\Components\Select::make('year')
                    ->label('Année')
                    ->options(self::getYearOptions())
                    ->default(Carbon::now()->year)
                    ->native(false),
            ])
            ->query(function (Builder $query, array $data): Builder {
                // Ce filtre n'affecte pas directement la requête SQL
                // Il est utilisé pour mettre à jour les colonnes de calcul financier
                return $query;
            })
            ->indicateUsing(function (array $data): array {
                $indicators = [];

                if ($data['month'] ?? null) {
                    $month = MonthEnum::from($data['month']);
                    $indicators['month'] = 'Mois: ' . $month->getLabel();
                }

                if ($data['year'] ?? null) {
                    $indicators['year'] = 'Année: ' . $data['year'];
                }

                return $indicators;
            });
    }

    private static function getFinancialDetailsAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('view_financials')
            ->label('Détails financiers')
            ->icon('heroicon-o-currency-dollar')
            ->color('success')
            ->modalContent(function (Property $record) {
                $paymentService = app(PaymentService::class);
                [$currentMonth, $currentYear] = self::getCurrentMonthYear();

                $stats = $paymentService->getPropertyFinancialStats(
                    $record->id,
                    $currentMonth,
                    $currentYear
                );

                return view('filament.resources.property-resource.components.financial-details', [
                    'stats' => $stats,
                    'property' => $record,
                    'month' => MonthEnum::from($currentMonth)->getLabel(),
                    'year' => $currentYear
                ]);
            })
            ->modalHeading(fn(Property $record) => "Détails financiers - {$record->name}")
            ->modalWidth('xl');
    }

    private static function getCurrentMonthYear(): array
    {
        $currentMonth = request()->input('tableFilters.month_year.month') ?? Carbon::now()->month;
        $currentYear = request()->input('tableFilters.month_year.year') ?? Carbon::now()->year;

        return [$currentMonth, $currentYear];
    }

    private static function getYearOptions(): array
    {
        $years = [];
        $currentYear = Carbon::now()->year;

        for ($i = $currentYear - 2; $i <= $currentYear + 1; $i++) {
            $years[$i] = $i;
        }

        return $years;
    }

    private static function formatCurrency(float $amount): string
    {
        return number_format($amount, 0, ',', ' ') . ' F CFA';
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\FlatRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProperties::route('/'),
            'create' => Pages\CreateProperty::route('/create'),
            // 'view' => Pages\ViewProperty::route('/{record}'),
            'edit' => Pages\EditProperty::route('/{record}/edit'),
        ];
    }

    // Implémentation des méthodes abstraites du trait HasAgencyPermissions
    protected static function getViewPermission(): string
    {
        return 'view_properties';
    }

    protected static function getCreatePermission(): string
    {
        return 'create_properties';
    }

    protected static function getEditPermission(): string
    {
        return 'edit_properties';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete_properties';
    }
}
