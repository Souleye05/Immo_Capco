<?php

namespace App\Filament\Tenant\Resources\DocumentResource\Pages;

use App\Filament\Tenant\Resources\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDocuments extends ListRecords
{
  protected static string $resource = DocumentResource::class;

  protected function getHeaderActions(): array
  {
    return [
      // No create action for tenant panel - tenants cannot create documents
    ];
  }
}
