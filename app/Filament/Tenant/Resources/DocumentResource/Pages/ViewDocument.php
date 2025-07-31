<?php

namespace App\Filament\Tenant\Resources\DocumentResource\Pages;

use App\Filament\Tenant\Resources\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDocument extends ViewRecord
{
  protected static string $resource = DocumentResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Actions\Action::make('download_contract')
        ->label('Télécharger Contrat')
        ->icon('heroicon-o-document-text')
        ->color('primary')
        ->action(function () {
          // This would typically generate and download the contract PDF
          \Filament\Notifications\Notification::make()
            ->title('Téléchargement du contrat')
            ->body('Le téléchargement du contrat va commencer.')
            ->success()
            ->send();
        }),
    ];
  }
}
