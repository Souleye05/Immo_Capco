<?php

namespace App\Filament\Tenant\Resources\UnpaidPaymentResource\Pages;

use App\Filament\Tenant\Resources\UnpaidPaymentResource;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListUnpaidPayments extends ListRecords
{
  protected static string $resource = UnpaidPaymentResource::class;

  public function getTitle(): string
  {
    return 'Mes Impayés';
  }

  public function getHeading(): string
  {
    return 'Mes Impayés';
  }

  public function getSubheading(): ?string
  {
    $unpaidCount = static::getResource()::getEloquentQuery()->count();

    if ($unpaidCount === 0) {
      return 'Félicitations ! Vous n\'avez aucun paiement en retard.';
    }

    if ($unpaidCount === 1) {
      return 'Vous avez 1 paiement en attente. Contactez votre agence pour plus d\'informations.';
    }

    return "Vous avez {$unpaidCount} paiements en attente. Contactez votre agence pour plus d'informations.";
  }

  public function getMaxContentWidth(): MaxWidth
  {
    return MaxWidth::Full;
  }

  protected function getHeaderActions(): array
  {
    return [
      Actions\Action::make('view_all_payments')
        ->label('Voir tous mes paiements')
        ->icon('heroicon-o-document-text')
        ->color('gray')
        ->url(route('filament.tenant.resources.payments.index', [
          'tenant' => Filament::getTenant()?->slug
        ]))
        ->visible(fn(): bool => $this->hasUnpaidPayments()),

      Actions\Action::make('help')
        ->label('Besoin d\'aide ?')
        ->icon('heroicon-o-question-mark-circle')
        ->color('primary')
        ->modalHeading('Contactez votre agence')
        ->modalDescription('Pour toute question concernant vos paiements en retard, veuillez contacter directement votre agence de gestion.')
        ->modalSubmitAction(false)
        ->modalCancelActionLabel('Fermer')
        ->visible(fn(): bool => $this->hasUnpaidPayments()),
    ];
  }

  protected function hasUnpaidPayments(): bool
  {
    return static::getResource()::getEloquentQuery()->count() > 0;
  }

  protected function getTableEmptyStateHeading(): ?string
  {
    return 'Aucun impayé trouvé';
  }

  protected function getTableEmptyStateDescription(): ?string
  {
    return 'Félicitations ! Vous n\'avez aucun paiement en retard. Tous vos paiements sont à jour.';
  }

  protected function getTableEmptyStateIcon(): ?string
  {
    return 'heroicon-o-check-circle';
  }

  protected function getTableEmptyStateActions(): array
  {
    return [
      Actions\Action::make('view_payments')
        ->label('Voir tous mes paiements')
        ->icon('heroicon-o-document-text')
        ->color('primary')
        ->url(route('filament.tenant.resources.payments.index', [
          'tenant' => Filament::getTenant()?->slug
        ])),
    ];
  }
}
