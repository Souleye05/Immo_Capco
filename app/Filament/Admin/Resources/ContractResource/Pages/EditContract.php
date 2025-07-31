<?php

namespace App\Filament\Admin\Resources\ContractResource\Pages;

use App\Enums\ContractStatus;
use App\Filament\Admin\Resources\ContractResource;
use App\Models\Contract;
use App\Models\Flat;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContract extends EditRecord
{
    protected static string $resource = ContractResource::class;

   protected function getHeaderActions(): array
    {
        return [
            // Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

}
