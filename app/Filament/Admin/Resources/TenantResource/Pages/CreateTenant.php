<?php

namespace App\Filament\Admin\Resources\TenantResource\Pages;

use App\Filament\Admin\Resources\TenantResource;
use App\Services\TenantUserService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Vérifier que l'email est fourni
        if (empty($data['email'])) {
            Notification::make()
                ->title('Erreur')
                ->body('L\'email est obligatoire pour créer un locataire.')
                ->danger()
                ->send();

            throw new \Exception('Email requis pour créer un locataire');
        }

        // Utiliser le TenantUserService pour créer le tenant avec utilisateur
        $tenantUserService = app(TenantUserService::class);

        try {
            $tenant = $tenantUserService->createTenantWithUser($data, $data['email']);

            // Vérifier si c'est un nouvel utilisateur ou existant
            $user = $tenant->user;
            if ($user->wasRecentlyCreated || !$user->email_verified_at) {
                Notification::make()
                    ->title('Locataire créé avec succès')
                    ->body("Le locataire {$data['name']} a été créé avec un nouveau compte utilisateur. Email: {$data['email']}, Mot de passe: password")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Locataire associé')
                    ->body("Le locataire {$data['name']} a été associé à un compte utilisateur existant ({$data['email']}).")
                    ->success()
                    ->send();
            }

            return $tenant;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur lors de la création')
                ->body('Erreur: ' . $e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        // Désactiver la notification par défaut car nous gérons les notifications dans handleRecordCreation
        return null;
    }
}
