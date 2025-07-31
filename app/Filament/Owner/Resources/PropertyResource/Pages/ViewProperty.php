<?php

namespace App\Filament\Owner\Resources\PropertyResource\Pages;

use App\Filament\Owner\Resources\PropertyResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProperty extends ViewRecord
{
  protected static string $resource = PropertyResource::class;

  protected function getHeaderActions(): array
  {
    return [
      // No edit or delete actions for owners
    ];
  }

  public function getTitle(): string
  {
    return 'Détails de la propriété';
  }
}
