<?php

namespace App\Filament\Tenant\Resources\UnpaidPaymentResource\Pages;

use App\Filament\Tenant\Resources\UnpaidPaymentResource;
use App\Models\Payment;
use Carbon\Carbon;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;

class ViewUnpaidPayment extends ViewRecord
{
  protected static string $resource = UnpaidPaymentResource::class;

  protected function getHeaderActions(): array
  {
    return [
      // No edit or delete actions as this is read-only
    ];
  }

  public function form(Form $form): Form
  {
    return $form
      ->schema([
        // Empty form schema since we're using infolist for display
      ]);
  }

  public function infolist(Infolist $infolist): Infolist
  {
    return $infolist
      ->schema([
        // Payment Overview Section
        Infolists\Components\Section::make('Informations du paiement')
          ->description('Détails de votre paiement impayé')
          ->icon('heroicon-o-document-text')
          ->schema([
            Infolists\Components\Grid::make(3)
              ->schema([
                Infolists\Components\TextEntry::make('numero')
                  ->label('N° Facture')
                  ->weight(FontWeight::Bold)
                  ->copyable()
                  ->copyMessage('Numéro de facture copié')
                  ->icon('heroicon-o-document-duplicate'),

                Infolists\Components\TextEntry::make('type')
                  ->label('Type de paiement')
                  ->badge()
                  ->color(fn(Payment $record): string => $record->getTypeBadgeColor())
                  ->formatStateUsing(fn(Payment $record): string => $record->getTypeLabel()),

                Infolists\Components\TextEntry::make('current_month')
                  ->label('Mois concerné')
                  ->placeholder('—')
                  ->formatStateUsing(function (Payment $record): string {
                    if (!$record->current_month || !$record->type->requiresMonth()) {
                      return '—';
                    }
                    return $record->current_month;
                  })
                  ->icon('heroicon-o-calendar'),
              ]),

            Infolists\Components\Grid::make(2)
              ->schema([
                Infolists\Components\TextEntry::make('date_payment')
                  ->label('Date d\'échéance')
                  ->date('d/m/Y')
                  ->color(function (Payment $record): string {
                    $dueDate = Carbon::parse($record->date_payment);
                    $today = Carbon::today();

                    if ($dueDate->isPast()) {
                      return 'danger';
                    } elseif ($dueDate->diffInDays($today) <= 7) {
                      return 'warning';
                    }
                    return 'gray';
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
                  }),

                Infolists\Components\TextEntry::make('overdue_status')
                  ->label('Statut d\'échéance')
                  ->badge()
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
                  }),
              ]),
          ]),

        // Payment Amounts Section
        Infolists\Components\Section::make('Montants')
          ->description('Détail des montants dus et versés')
          ->icon('heroicon-o-currency-dollar')
          ->schema([
            Infolists\Components\Grid::make(3)
              ->schema([
                Infolists\Components\TextEntry::make('amount')
                  ->label('Montant total dû')
                  ->money('XOF', locale: 'fr')
                  ->weight(FontWeight::Bold)
                  ->color('info')
                  ->icon('heroicon-o-banknotes'),

                Infolists\Components\TextEntry::make('amount_paid')
                  ->label('Montant versé')
                  ->formatStateUsing(function (Payment $record): string {
                    $amountPaid = $record->amount_paid;
                    return number_format($amountPaid, 0, ',', ' ') . ' FCFA';
                  })
                  ->color('success')
                  ->icon('heroicon-o-check-circle'),

                Infolists\Components\TextEntry::make('amount_remaining')
                  ->label('Montant restant')
                  ->formatStateUsing(function (Payment $record): string {
                    $amountRemaining = $record->amount_remaining;
                    return number_format($amountRemaining, 0, ',', ' ') . ' FCFA';
                  })
                  ->weight(FontWeight::Bold)
                  ->color('danger')
                  ->icon('heroicon-o-exclamation-triangle'),
              ]),

            // Payment Progress Bar
            Infolists\Components\TextEntry::make('payment_progress')
              ->label('Progression du paiement')
              ->formatStateUsing(function (Payment $record): string {
                $percentage = $record->amount > 0 ? ($record->amount_paid / $record->amount) * 100 : 0;
                return number_format($percentage, 1) . '% payé';
              })
              ->color(function (Payment $record): string {
                $percentage = $record->amount > 0 ? ($record->amount_paid / $record->amount) * 100 : 0;
                if ($percentage >= 80) return 'success';
                if ($percentage >= 50) return 'warning';
                return 'danger';
              })
              ->icon('heroicon-o-chart-bar'),
          ]),

        // Versement History Section
        Infolists\Components\Section::make('Historique des versements')
          ->description('Liste des paiements partiels effectués')
          ->icon('heroicon-o-clock')
          ->schema([
            Infolists\Components\RepeatableEntry::make('versement')
              ->label('')
              ->schema([
                Infolists\Components\Grid::make(4)
                  ->schema([
                    Infolists\Components\TextEntry::make('reference')
                      ->label('Référence')
                      ->placeholder('—')
                      ->icon('heroicon-o-hashtag'),

                    Infolists\Components\TextEntry::make('amount')
                      ->label('Montant')
                      ->money('XOF', locale: 'fr')
                      ->color('success')
                      ->weight(FontWeight::Medium)
                      ->icon('heroicon-o-banknotes'),

                    Infolists\Components\TextEntry::make('payment_method')
                      ->label('Méthode')
                      ->placeholder('—')
                      ->badge()
                      ->color('info')
                      ->icon('heroicon-o-credit-card'),

                    Infolists\Components\TextEntry::make('versement_date')
                      ->label('Date')
                      ->date('d/m/Y')
                      ->placeholder('—')
                      ->icon('heroicon-o-calendar'),
                  ]),
              ])
              ->visible(fn(Payment $record): bool => $record->versement->count() > 0),

            // Empty state for no versements
            Infolists\Components\TextEntry::make('no_versements')
              ->label('')
              ->formatStateUsing(fn(): string => 'Aucun versement effectué pour ce paiement.')
              ->color('gray')
              ->icon('heroicon-o-information-circle')
              ->visible(fn(Payment $record): bool => $record->versement->count() === 0),
          ]),

        // Property and Contract Details Section
        Infolists\Components\Section::make('Détails du bien et du contrat')
          ->description('Informations sur le bien immobilier et le contrat associé')
          ->icon('heroicon-o-home')
          ->schema([
            Infolists\Components\Grid::make(2)
              ->schema([
                // Property Information
                Infolists\Components\Group::make([
                  Infolists\Components\TextEntry::make('flat.property.name')
                    ->label('Nom du bien')
                    ->weight(FontWeight::Bold)
                    ->icon('heroicon-o-building-office'),

                  Infolists\Components\TextEntry::make('flat.property.address')
                    ->label('Adresse')
                    ->placeholder('—')
                    ->icon('heroicon-o-map-pin'),

                  Infolists\Components\TextEntry::make('flat.property.type')
                    ->label('Type de bien')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn($state): string => $state?->getLabel() ?? '—'),

                  Infolists\Components\TextEntry::make('flat.designation')
                    ->label('Appartement')
                    ->weight(FontWeight::Medium)
                    ->icon('heroicon-o-home'),

                  Infolists\Components\TextEntry::make('flat.type')
                    ->label('Type d\'appartement')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn($state): string => $state?->Label() ?? '—'),
                ])
                  ->columnSpan(1),

                // Contract Information
                Infolists\Components\Group::make([
                  Infolists\Components\TextEntry::make('contract.contract_number')
                    ->label('N° Contrat')
                    ->weight(FontWeight::Bold)
                    ->copyable()
                    ->copyMessage('Numéro de contrat copié')
                    ->icon('heroicon-o-document-text'),

                  Infolists\Components\TextEntry::make('contract.start_date')
                    ->label('Date de début')
                    ->date('d/m/Y')
                    ->icon('heroicon-o-calendar'),

                  Infolists\Components\TextEntry::make('contract.end_date')
                    ->label('Date de fin')
                    ->date('d/m/Y')
                    ->icon('heroicon-o-calendar'),

                  Infolists\Components\TextEntry::make('contract.monthly_rent')
                    ->label('Loyer mensuel')
                    ->money('XOF', locale: 'fr')
                    ->weight(FontWeight::Medium)
                    ->color('info')
                    ->icon('heroicon-o-banknotes'),

                  Infolists\Components\TextEntry::make('contract.status')
                    ->label('Statut du contrat')
                    ->badge()
                    ->color(fn($state): string => match ($state?->value) {
                      'active' => 'success',
                      'expired' => 'danger',
                      'terminated' => 'warning',
                      default => 'gray',
                    })
                    ->formatStateUsing(fn($state): string => $state?->label() ?? '—'),
                ])
                  ->columnSpan(1),
              ]),
          ]),

        // Agency Contact Information Section
        Infolists\Components\Section::make('Informations de contact de l\'agence')
          ->description('Contactez votre agence pour toute question concernant ce paiement')
          ->icon('heroicon-o-phone')
          ->schema([
            Infolists\Components\Grid::make(2)
              ->schema([
                Infolists\Components\TextEntry::make('agency.name')
                  ->label('Nom de l\'agence')
                  ->weight(FontWeight::Bold)
                  ->icon('heroicon-o-building-office-2'),

                Infolists\Components\TextEntry::make('agency.slug')
                  ->label('Code agence')
                  ->badge()
                  ->color('info')
                  ->icon('heroicon-o-identification'),
              ]),

            // Contact Instructions
            Infolists\Components\TextEntry::make('contact_instructions')
              ->label('')
              ->formatStateUsing(
                fn(): string =>
                'Pour effectuer ce paiement ou obtenir plus d\'informations, veuillez contacter votre agence. ' .
                  'Munissez-vous de votre numéro de facture (' . $this->record->numero . ') ' .
                  'et de votre numéro de contrat (' . ($this->record->contract?->contract_number ?? 'N/A') . ') ' .
                  'lors de votre prise de contact.'
              )
              ->color('info')
              ->icon('heroicon-o-information-circle'),
          ]),
      ]);
  }

  protected function getHeaderWidgets(): array
  {
    return [];
  }

  protected function getFooterWidgets(): array
  {
    return [];
  }
}
