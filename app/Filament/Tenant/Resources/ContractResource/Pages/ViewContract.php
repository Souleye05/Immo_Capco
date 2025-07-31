<?php

namespace App\Filament\Tenant\Resources\ContractResource\Pages;

use App\Filament\Tenant\Resources\ContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewContract extends ViewRecord
{
  protected static string $resource = ContractResource::class;

  protected function getHeaderActions(): array
  {
    return [
      // No actions for tenant view - read-only
    ];
  }
}
