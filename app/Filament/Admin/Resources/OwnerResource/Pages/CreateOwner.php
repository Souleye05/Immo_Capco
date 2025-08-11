<?php

namespace App\Filament\Admin\Resources\OwnerResource\Pages;

use App\Filament\Admin\Resources\OwnerResource;
use App\Services\OwnerUserService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class CreateOwner extends CreateRecord
{
    protected static string $resource = OwnerResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Extraire l'email des données
        $email = $data['email'] ?? null;

        if (!$email) {
            throw new \Exception('L\'adresse email est requise pour créer un propriétaire.');
        }

        // Supprimer l'email des données car il ne fait pas partie du modèle Owner directement
        unset($data['email']);

        try {
            // Récupérer l'agence courante
            $currentTenant = \Filament\Facades\Filament::getTenant();
            if (!$currentTenant) {
                throw new \Exception('Aucune agence sélectionnée');
            }

            // Utiliser le service pour créer le propriétaire avec l'utilisateur
            $ownerUserService = app(OwnerUserService::class);
            $owner = $ownerUserService->createOwnerWithAgency($data, $email, $currentTenant->id);

            // Déterminer si c'est un nouvel utilisateur ou existant
            $user = $owner->user;
            $isNewUser = $user->wasRecentlyCreated;

            // Désactiver les notifications par défaut
            $this->getCreatedNotification()?->send();

            // Envoyer une notification personnalisée
            if ($isNewUser) {
                Notification::make()
                    ->title('Propriétaire créé avec succès')
                    ->body("Un nouveau compte utilisateur a été créé pour {$user->email}. Le propriétaire peut maintenant accéder au portail propriétaire.")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Propriétaire créé avec succès')
                    ->body("Le propriétaire a été associé au compte utilisateur existant {$user->email}.")
                    ->success()
                    ->send();
            }

            return $owner;
        } catch (\Exception $e) {
            // Gestion d'erreur avec notification spécifique
            Notification::make()
                ->title('Erreur lors de la création du propriétaire')
                ->body($e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }

    protected function getCreatedNotification(): ?Notification
    {
        // Désactiver la notification par défaut
        return null;
    }
}
