<?php

namespace App\Filament\Admin\Resources\TenantResource\Pages;

use App\Filament\Admin\Resources\TenantResource;
use App\Services\TenantUserService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $originalEmail = $record->email;
        $newEmail = $data['email'] ?? null;

        // Si l'email a changé, nous devons gérer l'utilisateur
        if ($originalEmail !== $newEmail && $newEmail) {
            $tenantUserService = app(TenantUserService::class);

            try {
                // Trouver ou créer l'utilisateur avec le nouvel email
                $user = $tenantUserService->findOrCreateUserForTenant(
                    $newEmail,
                    $data['name'],
                    $record->agency_id
                );

                // Mettre à jour le tenant avec le nouvel utilisateur
                $data['user_id'] = $user->id;

                Notification::make()
                    ->title('Email mis à jour')
                    ->body("L'email du locataire a été changé de {$originalEmail} vers {$newEmail}. L'utilisateur associé a été mis à jour.")
                    ->success()
                    ->send();
            } catch (\Exception $e) {
                Notification::make()
                    ->title('Erreur lors de la mise à jour')
                    ->body('Erreur: ' . $e->getMessage())
                    ->danger()
                    ->send();

                throw $e;
            }
        }

        // Mettre à jour le tenant avec les nouvelles données
        $record->update($data);

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        // Désactiver la notification par défaut car nous gérons les notifications dans handleRecordUpdate
        return null;
    }
}
