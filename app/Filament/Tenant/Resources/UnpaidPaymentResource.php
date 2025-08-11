<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\UnpaidPaymentResource\Pages;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Flat;
use App\Enums\PaymentType;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UnpaidPaymentResource extends Resource
{
  protected static ?string $model = Payment::class;

  protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

  protected static ?string $navigationLabel = 'Impayés';

  protected static ?string $modelLabel = 'Impayé';

  protected static ?string $pluralModelLabel = 'Impayés';

  protected static ?int $navigationSort = 3;

  public static function getEloquentQuery(): Builder
  {
    $user = auth()->user();

    // If no authenticated user, return empty query
    if (!$user) {
      return parent::getEloquentQuery()->whereRaw('1 = 0');
    }

    return parent::getEloquentQuery()
      ->whereHas('tenant', function (Builder $query) use ($user) {
        // Primary matching by user_id for reliable tenant scoping
        $query->where('user_id', $user->id)
          // Fallback to name/email matching for legacy data
          ->orWhere(function ($subQuery) use ($user) {
            $subQuery->whereNull('user_id')
              ->where(function ($nameQuery) use ($user) {
                $nameQuery->where('name', 'like', "%{$user->name}%")
                  ->orWhere('phone', 'like', "%{$user->email}%");
              });
          });
      })
      // Filter for payments with remaining amount > 0 using optimized subquery
      ->whereRaw('(amount - COALESCE((SELECT SUM(amount) FROM versements WHERE versements.payment_id = payments.id), 0)) > 0')
      ->with([
        'contract' => function ($query) {
          $query->with(['property', 'agency']);
        },
        'tenant',
        'flat' => function ($query) {
          $query->with('property');
        },
        'agency',
        'versement' // Eager load versements for amount calculations
      ])
      // Enhanced default sorting with priority grouping
      ->orderByRaw("
        CASE 
          WHEN date_payment < CURDATE() THEN 1
          WHEN DATEDIFF(date_payment, CURDATE()) <= 7 THEN 2
          ELSE 3
        END ASC
      ") // Group by urgency: overdue first, then due soon, then upcoming
      ->orderBy('date_payment', 'asc') // Within each group, oldest first
      ->orderBy('amount', 'desc'); // Then by amount for consistent ordering
  }

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Placeholder::make('readonly_notice')
          ->content('Les informations d\'impayés ne peuvent pas être modifiées. Contactez votre agence pour toute question concernant vos paiements en retard.')
          ->columnSpanFull(),
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('numero')
          ->label('N° Facture')
          ->searchable()
          ->copyable()
          ->copyMessage('Numéro de facture copié')
          ->sortable()
          ->weight('medium')
          ->icon(function (Payment $record): ?string {
            $dueDate = Carbon::parse($record->date_payment);
            $today = Carbon::today();

            if ($dueDate->isPast()) {
              return 'heroicon-o-exclamation-triangle';
            } elseif ($dueDate->diffInDays($today) <= 7) {
              return 'heroicon-o-clock';
            }
            return 'heroicon-o-document-text';
          })
          ->iconColor(function (Payment $record): string {
            $dueDate = Carbon::parse($record->date_payment);
            $today = Carbon::today();

            if ($dueDate->isPast()) {
              return 'danger';
            } elseif ($dueDate->diffInDays($today) <= 7) {
              return 'warning';
            }
            return 'gray';
          }),

        Tables\Columns\TextColumn::make('type')
          ->label('Type')
          ->badge()
          ->color(fn(Payment $record): string => $record->getTypeBadgeColor())
          ->formatStateUsing(fn(Payment $record): string => $record->getTypeLabel())
          ->sortable(),

        Tables\Columns\TextColumn::make('flat.property.name')
          ->label('Bien')
          ->searchable()
          ->sortable()
          ->limit(30)
          ->tooltip(function (Payment $record): ?string {
            $property = $record->flat?->property;
            if (!$property) return null;
            return "{$property->name} - {$record->flat->name}";
          }),

        Tables\Columns\TextColumn::make('current_month')
          ->label('Mois')
          ->sortable()
          ->placeholder('—')
          ->formatStateUsing(function (Payment $record): string {
            if (!$record->current_month || !$record->type->requiresMonth()) return '—';
            return $record->current_month;
          }),

        Tables\Columns\TextColumn::make('amount')
          ->label('Montant dû')
          ->money('XOF', locale: 'fr')
          ->sortable()
          ->alignEnd()
          ->weight('medium'),

        Tables\Columns\TextColumn::make('amount_paid')
          ->label('Montant versé')
          ->sortable()
          ->alignEnd()
          ->color('success')
          ->formatStateUsing(function (Payment $record): string {
            $amountPaid = $record->amount_paid;
            return number_format($amountPaid, 0, ',', ' ') . ' FCFA';
          }),

        Tables\Columns\TextColumn::make('amount_remaining')
          ->label('Montant restant')
          ->sortable()
          ->alignEnd()
          ->weight('bold')
          ->color(function (Payment $record): string {
            $dueDate = Carbon::parse($record->date_payment);
            $today = Carbon::today();

            if ($dueDate->isPast()) {
              return 'danger'; // Overdue - red
            } elseif ($dueDate->diffInDays($today) <= 7) {
              return 'warning'; // Due soon - orange
            }
            return 'info'; // Future - blue
          })
          ->icon(function (Payment $record): ?string {
            $dueDate = Carbon::parse($record->date_payment);
            $today = Carbon::today();

            if ($dueDate->isPast()) {
              return 'heroicon-o-exclamation-triangle';
            } elseif ($dueDate->diffInDays($today) <= 7) {
              return 'heroicon-o-clock';
            }
            return 'heroicon-o-currency-dollar';
          })
          ->formatStateUsing(function (Payment $record): string {
            $amountRemaining = $record->amount_remaining;
            return number_format($amountRemaining, 0, ',', ' ') . ' FCFA';
          }),

        Tables\Columns\TextColumn::make('date_payment')
          ->label('Date d\'échéance')
          ->date('d/m/Y')
          ->sortable()
          ->color(function (Payment $record): string {
            $dueDate = Carbon::parse($record->date_payment);
            $today = Carbon::today();

            if ($dueDate->isPast()) {
              return 'danger'; // Overdue
            } elseif ($dueDate->diffInDays($today) <= 7) {
              return 'warning'; // Due soon
            }
            return 'gray'; // Future
          })
          ->icon(function (Payment $record): ?string {
            $dueDate = Carbon::parse($record->date_payment);
            $today = Carbon::today();

            if ($dueDate->isPast()) {
              return 'heroicon-o-exclamation-triangle';
            } elseif ($dueDate->diffInDays($today) <= 7) {
              return 'heroicon-o-clock';
            }
            return null;
          }),

        Tables\Columns\TextColumn::make('overdue_status')
          ->label('Priorité')
          ->badge()
          ->size('lg')
          ->weight('bold')
          ->color(function (Payment $record): string {
            $dueDate = Carbon::parse($record->date_payment);
            $today = Carbon::today();

            if ($dueDate->isPast()) {
              return 'danger';
            } elseif ($dueDate->diffInDays($today) <= 7) {
              return 'warning';
            }
            return 'success';
          })
          ->icon(function (Payment $record): string {
            $dueDate = Carbon::parse($record->date_payment);
            $today = Carbon::today();

            if ($dueDate->isPast()) {
              return 'heroicon-o-exclamation-triangle';
            } elseif ($dueDate->diffInDays($today) <= 7) {
              return 'heroicon-o-clock';
            }
            return 'heroicon-o-calendar';
          })
          ->formatStateUsing(function (Payment $record): string {
            $dueDate = Carbon::parse($record->date_payment);
            $today = Carbon::today();

            if ($dueDate->isPast()) {
              $daysPast = $today->diffInDays($dueDate);
              return "URGENT ({$daysPast}j de retard)";
            } elseif ($dueDate->diffInDays($today) <= 7) {
              $daysLeft = $dueDate->diffInDays($today);
              return "PROCHE ({$daysLeft}j restants)";
            }
            return 'À venir';
          })
          ->sortable(query: function ($query, string $direction): Builder {
            return $query->orderByRaw("
              CASE 
                WHEN date_payment < CURDATE() THEN 1
                WHEN DATEDIFF(date_payment, CURDATE()) <= 7 THEN 2
                ELSE 3
              END {$direction}
            ");
          }),
      ])
      ->filters([
        // Payment type filter
        SelectFilter::make('type')
          ->label('Type de paiement')
          ->options(PaymentType::getOptions())
          ->placeholder('Tous les types'),

        // Property filter
        SelectFilter::make('property')
          ->label('Bien')
          ->relationship('flat.property', 'name')
          ->searchable()
          ->preload()
          ->placeholder('Tous les biens')
          ->modifyQueryUsing(function (Builder $query, array $data) {
            if (isset($data['value']) && $data['value']) {
              return $query->whereHas('flat.property', function (Builder $q) use ($data) {
                $q->where('id', $data['value']);
              });
            }
            return $query;
          }),

        // Flat filter (dependent on property)
        SelectFilter::make('flat')
          ->label('Appartement')
          ->relationship('flat', 'designation')
          ->searchable()
          ->preload()
          ->placeholder('Tous les appartements')
          ->modifyQueryUsing(function (Builder $query, array $data) {
            if (isset($data['value']) && $data['value']) {
              return $query->where('flat_id', $data['value']);
            }
            return $query;
          }),

        // Month filter for rent payments
        SelectFilter::make('current_month')
          ->label('Mois')
          ->options([
            'Janvier 2024' => 'Janvier 2024',
            'Février 2024' => 'Février 2024',
            'Mars 2024' => 'Mars 2024',
            'Avril 2024' => 'Avril 2024',
            'Mai 2024' => 'Mai 2024',
            'Juin 2024' => 'Juin 2024',
            'Juillet 2024' => 'Juillet 2024',
            'Août 2024' => 'Août 2024',
            'Septembre 2024' => 'Septembre 2024',
            'Octobre 2024' => 'Octobre 2024',
            'Novembre 2024' => 'Novembre 2024',
            'Décembre 2024' => 'Décembre 2024',
            'Janvier 2025' => 'Janvier 2025',
            'Février 2025' => 'Février 2025',
            'Mars 2025' => 'Mars 2025',
            'Avril 2025' => 'Avril 2025',
            'Mai 2025' => 'Mai 2025',
            'Juin 2025' => 'Juin 2025',
            'Juillet 2025' => 'Juillet 2025',
            'Août 2025' => 'Août 2025',
            'Septembre 2025' => 'Septembre 2025',
            'Octobre 2025' => 'Octobre 2025',
            'Novembre 2025' => 'Novembre 2025',
            'Décembre 2025' => 'Décembre 2025',
          ])
          ->placeholder('Tous les mois'),

        // Overdue status filter
        SelectFilter::make('overdue_status')
          ->label('Statut d\'échéance')
          ->options([
            'overdue' => 'En retard',
            'due_soon' => 'Échéance proche (7 jours)',
            'upcoming' => 'À venir',
          ])
          ->placeholder('Tous les statuts')
          ->query(function (Builder $query, array $data): Builder {
            if (!isset($data['value']) || !$data['value']) {
              return $query;
            }

            $today = Carbon::today();

            return match ($data['value']) {
              'overdue' => $query->where('date_payment', '<', $today),
              'due_soon' => $query->where('date_payment', '>=', $today)
                ->where('date_payment', '<=', $today->copy()->addDays(7)),
              'upcoming' => $query->where('date_payment', '>', $today->copy()->addDays(7)),
              default => $query,
            };
          }),

        // Amount range filter
        Filter::make('amount_range')
          ->form([
            Forms\Components\Grid::make(2)
              ->schema([
                Forms\Components\TextInput::make('amount_from')
                  ->label('Montant minimum')
                  ->numeric()
                  ->suffix('FCFA')
                  ->placeholder('0'),
                Forms\Components\TextInput::make('amount_to')
                  ->label('Montant maximum')
                  ->numeric()
                  ->suffix('FCFA')
                  ->placeholder('1 000 000'),
              ]),
          ])
          ->query(function (Builder $query, array $data): Builder {
            return $query
              ->when(
                $data['amount_from'],
                fn(Builder $query, $amount): Builder => $query->where('amount', '>=', $amount),
              )
              ->when(
                $data['amount_to'],
                fn(Builder $query, $amount): Builder => $query->where('amount', '<=', $amount),
              );
          })
          ->indicateUsing(function (array $data): array {
            $indicators = [];

            if ($data['amount_from'] ?? null) {
              $indicators[] = 'Montant min: ' . number_format($data['amount_from'], 0, ',', ' ') . ' FCFA';
            }

            if ($data['amount_to'] ?? null) {
              $indicators[] = 'Montant max: ' . number_format($data['amount_to'], 0, ',', ' ') . ' FCFA';
            }

            return $indicators;
          }),
      ])
      ->actions([
        Tables\Actions\ViewAction::make()->label('Voir détails'),
      ])
      ->bulkActions([])
      ->defaultSort('date_payment', 'asc') // Oldest unpaid first with priority grouping
      ->striped()
      ->paginated([10, 25, 50])
      ->recordClasses(function (Payment $record): string {
        $dueDate = Carbon::parse($record->date_payment);
        $today = Carbon::today();

        if ($dueDate->isPast()) {
          return 'bg-red-50 border-l-4 border-red-500 dark:bg-red-950/20 dark:border-red-400';
        } elseif ($dueDate->diffInDays($today) <= 7) {
          return 'bg-orange-50 border-l-4 border-orange-500 dark:bg-orange-950/20 dark:border-orange-400';
        }
        return 'bg-blue-50 border-l-4 border-blue-500 dark:bg-blue-950/20 dark:border-blue-400';
      })
      ->emptyStateHeading('Aucun impayé trouvé')
      ->emptyStateDescription('Félicitations ! Vous n\'avez aucun paiement en retard.')
      ->emptyStateIcon('heroicon-o-check-circle');
  }

  public static function getRelations(): array
  {
    return [];
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListUnpaidPayments::route('/'),
      'view' => Pages\ViewUnpaidPayment::route('/{record}'),
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

  protected static ?int $cachedUnpaidCount = null;

public static function getNavigationBadge(): ?string
{
    static::$cachedUnpaidCount = static::$cachedUnpaidCount ?? static::getEloquentQuery()->count();

    return static::$cachedUnpaidCount > 0 ? (string) static::$cachedUnpaidCount : null;
}

public static function getNavigationBadgeColor(): ?string
{
    return static::$cachedUnpaidCount > 0 ? 'danger' : null;
}

}
