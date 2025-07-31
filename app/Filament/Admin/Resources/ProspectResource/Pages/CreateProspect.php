<?php

namespace App\Filament\Admin\Resources\ProspectResource\Pages;

use App\Filament\Admin\Resources\ProspectResource;
use App\Http\Requests\ProspectRequest;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateProspect extends CreateRecord
{
    protected static string $resource = ProspectResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
           ->title('Prospect créé avec succès!')
            ->body("Le prospect {$this->getRecord()->name} a été ajouté à la liste de prospects.")
            ->duration(5000);
    }

}
