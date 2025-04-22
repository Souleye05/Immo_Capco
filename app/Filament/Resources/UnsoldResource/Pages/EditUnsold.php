<?php

namespace App\Filament\Resources\UnsoldResource\Pages;

use App\Filament\Resources\UnsoldResource;
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
