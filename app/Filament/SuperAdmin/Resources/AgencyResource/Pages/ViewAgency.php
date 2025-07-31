<?php

namespace App\Filament\SuperAdmin\Resources\AgencyResource\Pages;

use App\Filament\SuperAdmin\Resources\AgencyResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAgency extends ViewRecord
{
  protected static string $resource = AgencyResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Actions\EditAction::make(),
    ];
  }
}
