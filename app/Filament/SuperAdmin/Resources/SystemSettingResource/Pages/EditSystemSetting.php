<?php

namespace App\Filament\SuperAdmin\Resources\SystemSettingResource\Pages;

use App\Filament\SuperAdmin\Resources\SystemSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSystemSetting extends EditRecord
{
  protected static string $resource = SystemSettingResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Actions\ViewAction::make(),
      Actions\DeleteAction::make(),
    ];
  }

  protected function mutateFormDataBeforeFill(array $data): array
  {
    // Handle JSON decoding for display in form
    if (in_array($data['type'], ['json', 'array']) && is_array($data['value'])) {
      $data['value'] = json_encode($data['value'], JSON_PRETTY_PRINT);
    }

    return $data;
  }

  protected function mutateFormDataBeforeSave(array $data): array
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
