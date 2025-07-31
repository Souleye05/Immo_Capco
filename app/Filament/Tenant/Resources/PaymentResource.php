<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Enums\PaymentType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Mes Paiements';

    protected static ?string $modelLabel = 'Paiement';

    protected static ?string $pluralModelLabel = 'Paiements';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->whereHas('tenant', function (Builder $query) use ($user) {
                $query->where('name', 'like', '%' . $user->name . '%')
                      ->orWhere('phone', 'like', '%' . $user->email . '%');
            })
            ->with(['contract.property', 'contract.agency', 'flat.property', 'tenant']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Placeholder::make('readonly_notice')
                    ->content('Les informations de paiement ne peuvent pas être modifiées. Contactez votre agence pour toute question.')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero')
                    ->label('Numéro de facture')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn(PaymentType $state): string => $state->getLabel())
                    ->colors([
                        'success' => PaymentType::LOYER->value,
                        'warning' => PaymentType::CAUTION->value,
                        'info' => PaymentType::COMMISSION->value,
                    ])
                    ->sortable(),

                TextColumn::make('flat.property.name')
                    ->label('Bien immobilier')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('contract.contract_number')
                    ->label('Contrat')
                    ->placeholder('N/A')
                    ->formatStateUsing(fn($state, $record) => $record->contract ? $state : 'N/A'),

                TextColumn::make('current_month')
                    ->label('Mois concerné')
                    ->sortable()
                    ->placeholder('N/A')
                    ->formatStateUsing(
                        fn($state): string =>
                        $state ? Carbon::createFromFormat('Y-m', $state)->translatedFormat('F Y') : 'N/A'
                    ),

                TextColumn::make('amount')
                    ->label('Montant dû')
                    ->formatStateUsing(fn($state): string => number_format($state, 0, ',', ' ') . ' FCFA')
                    ->sortable(),

                TextColumn::make('amount_paid')
                    ->label('Montant versé')
                    ->formatStateUsing(fn($state): string => number_format($state, 0, ',', ' ') . ' FCFA')
                    ->sortable()
                    ->color('success'),

                TextColumn::make('amount_remaining')
                    ->label('Montant restant')
                    ->formatStateUsing(fn($state): string => number_format($state, 0, ',', ' ') . ' FCFA')
                    ->sortable()
                    ->color(fn($state): string => $state > 0 ? 'danger' : 'success'),

                IconColumn::make('status')
                    ->label('Statut')
                    ->sortable()
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('date_payment')
                    ->label('Date de paiement')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->date('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('current_month')
                    ->label('Mois concerné')
                    ->options(function () {
                        return static::getEloquentQuery()
                            ->whereNotNull('current_month')
                            ->select('current_month')
                            ->distinct()
                            ->orderByDesc('current_month')
                            ->pluck('current_month')
                            ->filter(fn($val) => preg_match('/^\d{4}-\d{2}$/', $val))
                            ->mapWithKeys(fn($value) => [
                                $value => Carbon::createFromFormat('Y-m', $value)->translatedFormat('F Y')
                            ])
                            ->toArray();
                    }),

                SelectFilter::make('type')
                    ->label('Type de facture')
                    ->options(PaymentType::getOptions()),

                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        '1' => 'Payé',
                        '0' => 'Non payé',
                    ]),

                Filter::make('overdue')
                    ->label('Paiements en retard')
                    ->query(function (Builder $query): Builder {
                        return $query
                            ->whereRaw('(amount - (SELECT COALESCE(SUM(amount), 0) FROM versements WHERE versements.payment_id = payments.id)) > 0')
                            ->where('date_payment', '<', now());
                    }),

                Filter::make('current_month_filter')
                    ->label('Ce mois-ci')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->where('current_month', now()->format('Y-m'))
                    ),

                Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Du'),
                        Forms\Components\DatePicker::make('until')->label('Au'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date_payment', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date_payment', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Voir détails'),
            ])
            ->bulkActions([])
            ->defaultSort('date_payment', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Aucun paiement trouvé')
            ->emptyStateDescription('Vous n\'avez aucun paiement enregistré.')
            ->emptyStateIcon('heroicon-o-credit-card')
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'view' => Pages\ViewPayment::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $overdueCount = static::getEloquentQuery()
            ->whereRaw('(amount - (SELECT COALESCE(SUM(amount), 0) FROM versements WHERE versements.payment_id = payments.id)) > 0')
            ->where('date_payment', '<', now())
            ->count();

        return $overdueCount > 0 ? (string) $overdueCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() ? 'danger' : null;
    }
}
