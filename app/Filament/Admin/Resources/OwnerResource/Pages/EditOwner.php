<?php

namespace App\Filament\Admin\Resources\OwnerResource\Pages;

use App\Filament\Admin\Resources\OwnerResource;
use App\Services\OwnerUserService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class EditOwner extends EditRecord
{
    protected static string $resource = OwnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Récupérer l'email actuel et le nouvel email
        $currentEmail = $record->user?->email ?? null;
        $newEmail = $data['email'] ?? null;

        // Supprimer l'email des données car il ne fait pas partie du modèle Owner directement
        unset($data['email']);

        try {
            // Mettre à jour les données de base du propriétaire
            $record->update($data);

            // Si l'email a changé, utiliser le service pour gérer la réassociation
            if ($newEmail && $newEmail !== $currentEmail) {
                $ownerUserService = app(OwnerUserService::class);
                $user = $ownerUserService->updateOwnerUserAssociation($record, $newEmail);

                // Déterminer si c'est un nouvel utilisateur ou existant
                $isNewUser = $user->wasRecentlyCreated;

                // Envoyer une notification personnalisée
                if ($isNewUser) {
                    Notification::make()
                        ->title('Propriétaire mis à jour avec succès')
                        ->body("Un nouveau compte utilisateur a été créé pour {$newEmail}. Le propriétaire peut maintenant accéder au portail propriétaire.")
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Propriétaire mis à jour avec succès')
                        ->body("Le propriétaire a été associé au compte utilisateur existant {$newEmail}.")
                        ->success()
                        ->send();
                }
            } else {
                // Notification standard si pas de changement d'email
                Notification::make()
                    ->title('Propriétaire mis à jour avec succès')
                    ->body('Les informations du propriétaire ont été mises à jour.')
                    ->success()
                    ->send();
            }

            return $record;
        } catch (\Exception $e) {
            // Gestion d'erreur avec notification spécifique
            Notification::make()
                ->title('Erreur lors de la mise à jour du propriétaire')
                ->body($e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }

    protected function getSavedNotification(): ?Notification
    {
        // Désactiver la notification par défaut
        return null;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Ajouter l'email de l'utilisateur associé aux données du formulaire
        if ($this->record->user) {
            $data['email'] = $this->record->user->email;
        }

        return $data;
    }
}
