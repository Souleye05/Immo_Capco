<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Enums\ContractStatus;
use App\Filament\Resources\ContractResource;
use App\Models\Contract;
use App\Models\Flat;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateContract extends CreateRecord
{
    protected static string $resource = ContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
            Actions\EditAction::make(),
            // Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
{
    $existingActiveContract = Contract::where('tenant_id', $data['tenant_id'])
        ->where('flat_id', $data['flat_id'])
        ->where('status', ContractStatus::ACTIVE)
        ->exists();

    if ($existingActiveContract) {
        Notification::make()
            ->title('Erreur de validation')
            ->body('Ce locataire a déjà un contrat actif dans cet appartement.')
            ->danger()
            ->send();

        throw new \Exception('Contrat en double détecté');
    }

    return $data;
}

// protected function afterSave(): void
// {
//     $record = $this->record;

//     if (
//         $record->status === ContractStatus::ACTIVE &&
//         $record->flat_id &&
//         $record->tenant_id
//     ) {
//         Flat::where('id', $record->flat_id)->update([
//             'tenant_id' => $record->tenant_id,
//         ]);

//         Flat::where('tenant_id', $record->tenant_id)
//             ->where('id', '!=', $record->flat_id)
//             ->update(['tenant_id' => null]);

//         Notification::make()
//             ->title('Succès')
//             ->body('L\'appartement a été attribué au locataire avec succès.')
//             ->success()
//             ->send();
//     }
// }



}
