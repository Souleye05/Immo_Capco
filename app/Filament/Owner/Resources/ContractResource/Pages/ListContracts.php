<?php

namespace App\Filament\Owner\Resources\ContractResource\Pages;

use App\Filament\Owner\Resources\ContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContracts extends ListRecords
{
  protected static string $resource = ContractResource::class;

  protected function getHeaderActions(): array
  {
    return [
      // No create action for owners
    ];
  }

  public function getTitle(): string
  {
    return 'Mes Contrats';
  }

  protected function getHeaderWidgets(): array
  {
    return [
      // Add contract overview widgets here if needed
    ];
  }
}
