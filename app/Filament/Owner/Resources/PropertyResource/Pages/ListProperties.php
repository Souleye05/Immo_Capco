<?php

namespace App\Filament\Owner\Resources\PropertyResource\Pages;

use App\Filament\Owner\Resources\PropertyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProperties extends ListRecords
{
  protected static string $resource = PropertyResource::class;

  protected function getHeaderActions(): array
  {
    return [
      // No create action for owners
    ];
  }

  public function getTitle(): string
  {
    return 'Mes Propriétés';
  }

  protected function getHeaderWidgets(): array
  {
    return [
      // Add property overview widgets here if needed
    ];
  }
}
