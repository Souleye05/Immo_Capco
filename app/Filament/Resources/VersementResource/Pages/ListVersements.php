<?php

namespace App\Filament\Resources\VersementResource\Pages;

use App\Filament\Resources\VersementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVersements extends ListRecords
{
    protected static string $resource = VersementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
