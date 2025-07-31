<?php

namespace App\Filament\Admin\Resources\ContractResource\Pages;

use App\Filament\Admin\Resources\ContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

// class ListContracts extends ListRecords
// {
//     protected static string $resource = ContractResource::class;

//     protected function getHeaderActions(): array
//     {
//         return [
//             Actions\CreateAction::make()
//                 ->label('Ajouter un contrat')
//                 ->icon('heroicon-o-plus')
//                 ->color('primary'),
//         ];
//     }


// }
namespace App\Filament\Admin\Resources\ContractResource\Pages;

use App\Filament\Admin\Resources\ContractResource;
use App\Models\Contract;
use Filament\Actions;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Concerns\ExposesTableToWidgets;

class ListContracts extends ListRecords
{
    use ExposesTableToWidgets;
    
    protected static string $resource = ContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Ajouter un contrat')
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ContractResource\Widgets\ContractStatsWidget::class,
        ];
    }
}
