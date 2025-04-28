<?php

namespace App\Filament\Resources\CategorieDepenseResource\Pages;

use App\Filament\Resources\CategorieDepenseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCategorieDepenses extends ListRecords
{
    protected static string $resource = CategorieDepenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
