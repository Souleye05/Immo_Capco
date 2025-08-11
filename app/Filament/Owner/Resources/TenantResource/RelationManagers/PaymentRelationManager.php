<?php

namespace App\Filament\Owner\Resources\TenantResource\RelationManagers;

use App\Enums\PaymentType;
use App\Models\Payment;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PaymentRelationManager extends RelationManager
{
  protected static string $relationship = 'allPayments';

  protected static ?string $title = 'Historique des Paiements';
  protected static ?string $modelLabel = 'Paiement';
  protected static ?string $pluralModelLabel = 'Paiements';
  protected static ?string $icon = 'heroicon-o-credit-card';

  /**
   * Filter payments to only show those belonging to the owner's properties
   */
  public function getEloquentQuery(): Builder
  {
    $user = auth()->user();

    if (!$user) {
      return parent::getEloquentQuery()->whereRaw('1 = 0');
    }

    return parent::getEloquentQuery()
      ->whereHas('contract', function (Builder $contractQuery) use ($user) {
        $contractQuery->whereHas('property', function (Builder $propertyQuery) use ($user) {
          $propertyQuery->whereHas('owner', function (Builder $ownerQuery) use ($user) {
            // Primary filtering: Direct user_id relation
            $ownerQuery->where('user_id', $user->id);
          })
            // Fallback filtering: Email matching
            ->orWhereHas('owner', function (Builder $ownerQuery) use ($user) {
              $ownerQuery->where('email', $user->email);
            })
            // Additional fallback: Name matching
            ->orWhereHas('owner', function (Builder $ownerQuery) use ($user) {
              $ownerQuery->where('name', 'like', '%' . trim($user->name) . '%')
                ->whereNotNull('name')
                ->where('name', '!=', '');
            });
        });
      })
      ->with(['contract.property', 'versement'])
      ->orderBy('date_payment', 'desc');
  }

  public function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Section::make('Informations du paiement')
          ->schema([
            Forms\Components\Grid::make(2)
              ->schema([
                Forms\Components\TextInput::make('numero')
                  ->label('Numéro de facture')
                  ->disabled()
                  ->dehydrated(false),

                Forms\Components\Select::make('type')
                  ->label('Type de paiement')
                  ->options(PaymentType::options())
                  ->disabled()
                  ->dehydrated(false),

                Forms\Components\TextInput::make('contract.property.name')
                  ->label('Propriété')
                  ->disabled()
                  ->dehydrated(false),

                Forms\Components\TextInput::make('current_month')
                  ->label('Mois concerné')
                  ->disabled()
                  ->dehydrated(false),

                Forms\Components\TextInput::make('amount')
                  ->label('Montant dû')
                  ->disabled()
                  ->dehydrated(false)
                  ->formatStateUsing(function ($state) {
                    return $state ? number_format($state, 0, ',', ' ') . ' F CFA' : '-';
                  }),

                Forms\Components\TextInput::make('amount_paid')
                  ->label('Montant payé')
                  ->disabled()
                  ->dehydrated(false)
                  ->formatStateUsing(function ($state) {
                    return $state ? number_format($state, 0, ',', ' ') . ' F CFA' : '0 F CFA';
                  }),

                Forms\Components\DatePicker::make('date_payment')
                  ->label('Date d\'échéance')
                  ->disabled()
                  ->dehydrated(false),

                Forms\Components\Toggle::make('status')
                  ->label('Payé intégralement')
                  ->disabled()
                  ->dehydrated(false),
              ]),

            Forms\Components\Section::make('Détails des versements')
              ->schema([
                Forms\Components\Repeater::make('versement')
                  ->label('Versements effectués')
                  ->relationship('versement')
                  ->schema([
                    Forms\Components\Grid::make(3)
                      ->schema([
                        Forms\Components\TextInput::make('reference')
                          ->label('Référence')
                          ->disabled()
                          ->dehydrated(false),

                        Forms\Components\TextInput::make('amount')
                          ->label('Montant')
                          ->disabled()
                          ->dehydrated(false)
                          ->formatStateUsing(function ($state) {
                            return $state ? number_format($state, 0, ',', ' ') . ' F CFA' : '-';
                          }),

                        Forms\Components\DatePicker::make('versement_date')
                          ->label('Date de versement')
                          ->disabled()
                          ->dehydrated(false),
                      ]),
                  ])
                  ->disabled()
                  ->dehydrated(false)
                  ->columnSpanFull(),
              ])
              ->collapsible()
              ->collapsed(),
          ]),
      ]);
  }

  public function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('numero')
      ->columns([
        Tables\Columns\TextColumn::make('numero')
          ->label('N° Facture')
          ->searchable()
          ->sortable()
          ->weight('medium')
          ->copyable(),

        Tables\Columns\TextColumn::make('type')
          ->label('Type')
          ->badge()
          ->color(function (PaymentType $state): string {
            return match ($state) {
              PaymentType::LOYER => 'success',
              PaymentType::CAUTION => 'warning',
              PaymentType::COMMISSION => 'info',
            };
          })
          ->formatStateUsing(fn(PaymentType $state): string => $state->label()),

        Tables\Columns\TextColumn::make('current_month')
          ->label('Mois')
          ->searchable()
          ->sortable()
          ->toggleable(),

        Tables\Columns\TextColumn::make('date_payment')
          ->label('Échéance')
          ->date('d/m/Y')
          ->sortable()
          ->color(function (Model $record): string {
            if (!$record->date_payment) return 'gray';

            $isOverdue = Carbon::parse($record->date_payment)->isPast() && !$record->status;
            $isDueSoon = Carbon::parse($record->date_payment)->isBetween(now(), now()->addDays(7)) && !$record->status;

            if ($isOverdue) {
              return 'danger';
            } elseif ($isDueSoon) {
              return 'warning';
            }

            return 'gray';
          })
          ->icon(function (Model $record): ?string {
            if (!$record->date_payment) return null;

            $isOverdue = Carbon::parse($record->date_payment)->isPast() && !$record->status;
            $isDueSoon = Carbon::parse($record->date_payment)->isBetween(now(), now()->addDays(7)) && !$record->status;

            if ($isOverdue) {
              return 'heroicon-o-exclamation-triangle';
            } elseif ($isDueSoon) {
              return 'heroicon-o-clock';
            }

            return null;
          }),

        Tables\Columns\TextColumn::make('amount')
          ->label('Montant dû')
          ->formatStateUsing(function ($state) {
            return $state ? number_format($state, 0, ',', ' ') . ' F' : '-';
          })
          ->alignEnd()
          ->sortable(),

        Tables\Columns\TextColumn::make('amount_paid')
          ->label('Payé')
          ->formatStateUsing(function ($state) {
            return $state ? number_format($state, 0, ',', ' ') . ' F' : '0 F';
          })
          ->alignEnd()
          ->color(function (Model $record): string {
            $amountPaid = $record->amount_paid ?? 0;
            $amount = $record->amount ?? 0;

            if ($amountPaid >= $amount) {
              return 'success';
            } elseif ($amountPaid > 0) {
              return 'warning';
            }

            return 'danger';
          }),

        Tables\Columns\TextColumn::make('amount_remaining')
          ->label('Restant')
          ->formatStateUsing(function (Model $record) {
            $remaining = $record->amount_remaining ?? 0;
            return $remaining > 0 ? number_format($remaining, 0, ',', ' ') . ' F' : '-';
          })
          ->alignEnd()
          ->color(function (Model $record): string {
            $remaining = $record->amount_remaining ?? 0;
            return $remaining > 0 ? 'danger' : 'success';
          }),

        Tables\Columns\TextColumn::make('payment_status')
          ->label('Statut')
          ->badge()
          ->color(function (Model $record): string {
            $amountPaid = $record->amount_paid ?? 0;
            $amount = $record->amount ?? 0;
            $isOverdue = Carbon::parse($record->date_payment)->isPast() && !$record->status;

            if ($amountPaid >= $amount) {
              return 'success';
            } elseif ($amountPaid > 0) {
              return $isOverdue ? 'warning' : 'info';
            } elseif ($isOverdue) {
              return 'danger';
            }

            return 'gray';
          })
          ->formatStateUsing(function (Model $record): string {
            $amountPaid = $record->amount_paid ?? 0;
            $amount = $record->amount ?? 0;
            $isOverdue = Carbon::parse($record->date_payment)->isPast() && !$record->status;

            if ($amountPaid >= $amount) {
              return 'Payé';
            } elseif ($amountPaid > 0) {
              return $isOverdue ? 'Partiel (En retard)' : 'Partiel';
            } elseif ($isOverdue) {
              return 'En retard';
            }

            return 'En attente';
          }),

        Tables\Columns\TextColumn::make('contract.property.name')
          ->label('Propriété')
          ->searchable()
          ->badge()
          ->color('info')
          ->toggleable(),

        Tables\Columns\TextColumn::make('created_at')
          ->label('Créé le')
          ->date('d/m/Y')
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('type')
          ->label('Type de paiement')
          ->options(PaymentType::options())
          ->multiple(),

        Tables\Filters\SelectFilter::make('property')
          ->label('Propriété')
          ->relationship('contract.property', 'name')
          ->searchable()
          ->preload(),

        Tables\Filters\SelectFilter::make('payment_status')
          ->label('Statut de paiement')
          ->options([
            'paid' => 'Payé intégralement',
            'partial' => 'Partiellement payé',
            'unpaid' => 'Non payé',
            'overdue' => 'En retard',
          ])
          ->query(function (Builder $query, array $data): Builder {
            if (!$data['value']) {
              return $query;
            }

            return match ($data['value']) {
              'paid' => $query->where('status', true),
              'partial' => $query->where('status', false)
                ->whereHas('versement'),
              'unpaid' => $query->where('status', false)
                ->whereDoesntHave('versement'),
              'overdue' => $query->where('status', false)
                ->where('date_payment', '<', now()),
              default => $query,
            };
          }),

        Tables\Filters\Filter::make('date_range')
          ->form([
            Forms\Components\DatePicker::make('from')
              ->label('Du'),
            Forms\Components\DatePicker::make('until')
              ->label('Au'),
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
          })
          ->indicateUsing(function (array $data): array {
            $indicators = [];

            if ($data['from'] ?? null) {
              $indicators[] = 'Du ' . Carbon::parse($data['from'])->format('d/m/Y');
            }

            if ($data['until'] ?? null) {
              $indicators[] = 'Au ' . Carbon::parse($data['until'])->format('d/m/Y');
            }

            return $indicators;
          }),

        Tables\Filters\Filter::make('overdue_only')
          ->label('Paiements en retard uniquement')
          ->query(
            fn(Builder $query): Builder =>
            $query->where('status', false)
              ->where('date_payment', '<', now())
          )
          ->toggle(),

        Tables\Filters\Filter::make('current_month')
          ->label('Mois en cours')
          ->query(
            fn(Builder $query): Builder =>
            $query->whereMonth('date_payment', now()->month)
              ->whereYear('date_payment', now()->year)
          )
          ->toggle(),

        Tables\Filters\Filter::make('has_partial_payments')
          ->label('Avec paiements partiels')
          ->query(
            fn(Builder $query): Builder =>
            $query->where('status', false)
              ->whereHas('versement')
          )
          ->toggle(),
      ])
      ->headerActions([
        // No create action since owners cannot create payments
      ])
      ->actions([
        Tables\Actions\ViewAction::make()
          ->label('Voir')
          ->icon('heroicon-o-eye'),

        Tables\Actions\Action::make('view_versements')
          ->label('Voir versements')
          ->icon('heroicon-o-banknotes')
          ->color('info')
          ->modalHeading(fn(Payment $record): string => "Versements - Facture {$record->numero}")
          ->modalContent(function (Payment $record): \Illuminate\Contracts\View\View {
            $versements = $record->versement()->orderBy('versement_date', 'desc')->get();
            return view('filament.owner.modals.payment-versements', [
              'payment' => $record,
              'versements' => $versements,
            ]);
          })
          ->modalSubmitAction(false)
          ->modalCancelActionLabel('Fermer')
          ->visible(fn(Payment $record): bool => $record->versement()->exists()),
      ])
      ->bulkActions([
        // No bulk actions for security reasons
      ])
      ->defaultSort('date_payment', 'desc')
      ->striped()
      ->emptyStateHeading('Aucun paiement trouvé')
      ->emptyStateDescription('Ce locataire n\'a actuellement aucun paiement enregistré dans vos propriétés.')
      ->emptyStateIcon('heroicon-o-credit-card')
      ->poll('30s') // Auto-refresh every 30 seconds to show updated payment status
      ->deferLoading();
  }

  /**
   * Get payment statistics for the header
   */
  public function getTableSummary(): ?array
  {
    $query = $this->getEloquentQuery();

    $totalPayments = $query->count();
    $totalAmount = $query->sum('amount');
    $totalPaid = $query->get()->sum('amount_paid');
    $totalOutstanding = $totalAmount - $totalPaid;
    $overdueCount = $query->where('status', false)
      ->where('date_payment', '<', now())
      ->count();

    return [
      Tables\Columns\Summarizers\Summarizer::make()
        ->label('Statistiques des paiements')
        ->using(function () use ($totalPayments, $totalAmount, $totalPaid, $totalOutstanding, $overdueCount) {
          return [
            'Total factures: ' . $totalPayments,
            'Montant total: ' . number_format($totalAmount, 0, ',', ' ') . ' F CFA',
            'Montant payé: ' . number_format($totalPaid, 0, ',', ' ') . ' F CFA',
            'Montant restant: ' . number_format($totalOutstanding, 0, ',', ' ') . ' F CFA',
            'Factures en retard: ' . $overdueCount,
          ];
        }),
    ];
  }

  /**
   * Validate that a payment belongs to the authenticated owner
   */
  private function validatePaymentAccess(Payment $payment, ?\App\Models\User $user = null): bool
  {
    if ($user === null) {
      $user = auth()->user();
    }

    if (!$user) {
      return false;
    }

    try {
      return $payment->contract()
        ->whereHas('property', function (Builder $propertyQuery) use ($user) {
          $propertyQuery->whereHas('owner', function (Builder $ownerQuery) use ($user) {
            // Primary check: Direct user_id relation
            $ownerQuery->where('user_id', $user->id);
          })
            // Fallback: Email matching
            ->orWhereHas('owner', function (Builder $ownerQuery) use ($user) {
              $ownerQuery->where('email', $user->email);
            })
            // Additional fallback: Name matching
            ->orWhereHas('owner', function (Builder $ownerQuery) use ($user) {
              $ownerQuery->where('name', 'like', '%' . trim($user->name) . '%')
                ->whereNotNull('name')
                ->where('name', '!=', '');
            });
        })
        ->exists();
    } catch (\Exception $e) {
      \Log::warning('Error validating payment access', [
        'user_id' => $user->id,
        'payment_id' => $payment->id,
        'error' => $e->getMessage(),
      ]);
      return false;
    }
  }

  /**
   * Override to add security validation
   */
  public function canView(Model $record): bool
  {
    $user = auth()->user();

    if (!$user) {
      return false;
    }

    // Check if user can access owner panel
    if (!$user->canAccessOwnerPanel()) {
      return false;
    }

    // Validate payment access
    return $this->validatePaymentAccess($record, $user);
  }

  /**
   * Owners cannot create payments
   */
  public function canCreate(): bool
  {
    return false;
  }

  /**
   * Owners cannot edit payments
   */
  public function canEdit(Model $record): bool
  {
    return false;
  }

  /**
   * Owners cannot delete payments
   */
  public function canDelete(Model $record): bool
  {
    return false;
  }
}
