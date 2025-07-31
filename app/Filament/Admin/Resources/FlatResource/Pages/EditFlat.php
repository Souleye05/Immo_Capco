<?php

namespace App\Filament\Admin\Resources\FlatResource\Pages;

use App\Filament\Admin\Resources\FlatResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFlat extends EditRecord
{
    protected static string $resource = FlatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
