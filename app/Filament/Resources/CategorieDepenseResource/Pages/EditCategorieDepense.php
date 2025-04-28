<?php

namespace App\Filament\Resources\CategorieDepenseResource\Pages;

use App\Filament\Resources\CategorieDepenseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCategorieDepense extends EditRecord
{
    protected static string $resource = CategorieDepenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
