<?php

namespace App\Filament\Admin\Resources\PrestataireResource\Pages;

use App\Filament\Admin\Resources\PrestataireResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPrestataire extends EditRecord
{
    protected static string $resource = PrestataireResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
