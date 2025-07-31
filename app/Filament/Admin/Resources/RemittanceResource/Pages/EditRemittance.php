<?php

namespace App\Filament\Admin\Resources\RemittanceResource\Pages;

use App\Filament\Admin\Resources\RemittanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRemittance extends EditRecord
{
    protected static string $resource = RemittanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
