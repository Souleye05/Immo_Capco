<?php

namespace App\Filament\Admin\Resources\VersementResource\Pages;

use App\Filament\Admin\Resources\VersementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVersement extends EditRecord
{
    protected static string $resource = VersementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
