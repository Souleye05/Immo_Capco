<?php

namespace App\Filament\Admin\Resources\UnsoldResource\Pages;

use App\Filament\Admin\Resources\UnsoldResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUnsold extends EditRecord
{
    protected static string $resource = UnsoldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
