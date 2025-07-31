<?php

namespace App\Filament\SuperAdmin\Resources\GlobalUserResource\Pages;

use App\Filament\SuperAdmin\Resources\GlobalUserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGlobalUser extends CreateRecord
{
  protected static string $resource = GlobalUserResource::class;
}
