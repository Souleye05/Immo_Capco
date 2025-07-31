<?php

namespace App\Filament\Admin\Resources\CategorieDepenseResource\Pages;

use App\Filament\Admin\Resources\CategorieDepenseResource;
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
