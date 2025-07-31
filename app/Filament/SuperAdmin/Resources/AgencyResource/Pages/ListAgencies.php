<?php

namespace App\Filament\SuperAdmin\Resources\AgencyResource\Pages;

use App\Filament\SuperAdmin\Resources\AgencyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAgencies extends ListRecords
{
  protected static string $resource = AgencyResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Actions\CreateAction::make(),
    ];
  }
}
