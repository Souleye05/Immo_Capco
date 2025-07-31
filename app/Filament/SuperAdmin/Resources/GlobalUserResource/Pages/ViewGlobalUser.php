<?php

namespace App\Filament\SuperAdmin\Resources\GlobalUserResource\Pages;

use App\Filament\SuperAdmin\Resources\GlobalUserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGlobalUser extends ViewRecord
{
  protected static string $resource = GlobalUserResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Actions\EditAction::make(),
    ];
  }
}
