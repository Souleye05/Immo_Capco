<?php

namespace App\Filament\Tenant\Resources\ContractResource\Pages;

use App\Filament\Tenant\Resources\ContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContracts extends ListRecords
{
    protected static string $resource = ContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for tenant panel - tenants cannot create contracts
        ];
    }
}
