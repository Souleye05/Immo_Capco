<?php

namespace App\Filament\SuperAdmin\Resources\SystemSettingResource\Pages;

use App\Filament\SuperAdmin\Resources\SystemSettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSystemSetting extends CreateRecord
{
  protected static string $resource = SystemSettingResource::class;

  protected function mutateFormDataBeforeCreate(array $data): array
  {
    // Handle JSON encoding for json and array types
    if (in_array($data['type'], ['json', 'array']) && is_string($data['value'])) {
      $decoded = json_decode($data['value'], true);
      if (json_last_error() === JSON_ERROR_NONE) {
        $data['value'] = $decoded;
      }
    }

    return $data;
  }
}
