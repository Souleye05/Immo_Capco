<?php

namespace App\Filament\Owner\Resources\RevenueResource\Pages;

use App\Filament\Owner\Resources\RevenueResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRevenue extends ViewRecord
{
  protected static string $resource = RevenueResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Actions\Action::make('download_receipt')
        ->label('Télécharger le reçu')
        ->icon('heroicon-o-document-arrow-down')
        ->color('info')
        ->visible(fn(): bool => $this->record->status)
        ->url(fn(): string => route('payments.receipt', $this->record))
        ->openUrlInNewTab(),
    ];
  }

  public function getTitle(): string
  {
    return 'Paiement N° ' . $this->record->numero;
  }
}
