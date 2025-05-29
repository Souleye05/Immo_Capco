<?php

namespace App\Filament\Resources\TenantResource\Pages;
use App\Filament\Resources\TenantResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Notifications\Notification;

class ViewTenant extends ViewRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Components\Section::make('Informations personnelles')
                ->description('Coordonnées du locataire')
                ->icon('heroicon-o-user')
                ->schema([
                    Components\TextEntry::make('name')
                        ->label('Nom complet')
                        ->size(Components\TextEntry\TextEntrySize::Large)
                        ->weight('bold')
                        ->color('primary')
                        ->icon('heroicon-o-identification'),
                    
                    Components\TextEntry::make('phone')
                        ->label('Téléphone')
                        ->copyable()
                        ->copyMessage('Téléphone copié !')
                        ->copyMessageDuration(1500)
                        ->icon('heroicon-o-phone')
                        ->color('success')
                        ->placeholder('Non renseigné'),

                    Components\TextEntry::make('address')
                        ->label('Adresse')
                        ->copyable()
                        ->copyMessage('Adresse copiée !')
                        ->copyMessageDuration(1500)
                        ->icon('heroicon-o-map-pin')
                        ->color('info')
                        ->placeholder('Non renseignée'),
                ])
                ->columns(3)
                ->collapsible(),

            Components\Section::make('Informations de location')
                ->description('Informations de base de la location')
                ->icon('heroicon-o-calendar')
                ->schema([
                    // Informations sur l'appartement
                    Components\TextEntry::make('flat.reference')
                        ->label('Référence appartement')
                        ->icon('heroicon-o-home')
                        ->color('primary')
                        ->placeholder('Appartement non assigné'),

                    Components\TextEntry::make('flat.type')
                        ->label('Type d\'appartement')
                        ->icon('heroicon-o-squares-2x2')
                        ->color('info')
                        ->placeholder('Type non renseigné'),

                    // Informations sur la propriété
                    Components\TextEntry::make('flat.property.name')
                        ->label('Propriété')
                        ->icon('heroicon-o-building-office')
                        ->color('secondary')
                        ->placeholder('Propriété non assignée'),

                    Components\TextEntry::make('flat.property.address')
                        ->label('Adresse de la propriété')
                        ->copyable()
                        ->copyMessage('Adresse copiée !')
                        ->copyMessageDuration(1500)
                        ->icon('heroicon-o-map-pin')
                        ->color('info')
                        ->placeholder('Adresse non renseignée'),

                    Components\TextEntry::make('flat.property.type')
                        ->label('Type de propriété')
                        ->icon('heroicon-o-building-library')
                        ->color('gray')
                        ->placeholder('Type non renseigné'),

                    Components\TextEntry::make('flat.property.owner.name')
                        ->label('Propriétaire')
                        ->icon('heroicon-o-user-circle')
                        ->color('gray')
                        ->placeholder('Propriétaire non renseigné'),

                    // Informations de loyer
                    Components\TextEntry::make('flat.loyer')
                        ->label('Montant du loyer')
                        ->money('EUR')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->placeholder('Montant non renseigné'),

                    Components\TextEntry::make('flat.caution')
                        ->label('Caution')
                        ->money('EUR')
                        ->icon('heroicon-o-shield-check')
                        ->color('warning')
                        ->placeholder('Caution non renseignée'),

                    // Statut de paiement (vous devrez peut-être l'ajouter au modèle ou calculer via les payments)
                    Components\TextEntry::make('payment.status')
                        ->label('Statut du paiement')
                        ->badge()
                        ->icon('heroicon-o-check-circle')
                        ->color(fn (?string $state): string => match ($state) {
                            '1' => 'success',
                            '0' => 'danger',
                            default => 'gray',
                        })
                        ->formatStateUsing(fn (?string $state): string => match ($state) {
                            '1' => 'Payé',
                            '0' => 'Pas payé',
                            default => 'Non renseigné',
                        })
                        ->placeholder('Statut non renseigné'),

                    // Dates de location
                    Components\TextEntry::make('created_at')
                        ->label('Date de début de location')
                        ->date('d/m/Y')
                        ->icon('heroicon-o-calendar')
                        ->color('primary')
                        ->placeholder('Date non renseignée'),


                ])->columns(2)
                ->collapsible(),

            Components\Section::make('Informations supplémentaires')
                ->description('Détails additionnels sur le locataire')
                ->icon('heroicon-o-information-circle')
                ->schema([
                    Components\TextEntry::make('notes')
                        ->label('Notes')
                        ->placeholder('Aucune note disponible')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->copyable()
                        ->copyMessage('Note copiée !')
                        ->copyMessageDuration(1500),
                    
                    Components\TextEntry::make('created_at')
                        ->label('Créé le')
                        ->dateTime('d/m/Y H:i'),

                    Components\TextEntry::make('updated_at')
                        ->label('Mis à jour le')
                        ->dateTime('d/m/Y H:i'),
                ])->columns(2)
                ->collapsible(),
          
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Locataire mis à jour')
            ->body('Les informations du locataire ont été mises à jour avec succès.');
    }
}                 