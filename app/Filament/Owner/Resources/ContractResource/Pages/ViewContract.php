<?php

namespace App\Filament\Owner\Resources\ContractResource\Pages;

use App\Filament\Owner\Resources\ContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewContract extends ViewRecord
{
  protected static string $resource = ContractResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Actions\Action::make('download_contract')
        ->label('Télécharger le contrat')
        ->icon('heroicon-o-document-arrow-down')
        ->color('info')
        ->url(fn(): string => route('contracts.pdf', $this->record))
        ->openUrlInNewTab(),
    ];
  }

  public function getTitle(): string
  {
    return 'Contrat N° ' . $this->record->contract_number;
  }
}
