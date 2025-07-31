<?php

namespace App\Filament\Owner\Resources\RevenueResource\Pages;

use App\Filament\Owner\Resources\RevenueResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRevenues extends ListRecords
{
  protected static string $resource = RevenueResource::class;

  protected function getHeaderActions(): array
  {
    return [
      // No create action for owners
    ];
  }

  public function getTitle(): string
  {
    return 'Mes Revenus';
  }

  protected function getHeaderWidgets(): array
  {
    return [
      // Add revenue overview widgets here if needed
    ];
  }
}
