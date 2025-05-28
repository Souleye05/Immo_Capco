<?php

namespace App\Filament\Resources\ProspectResource\Pages;

use App\Filament\Resources\ProspectResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewProspect extends ViewRecord
{
    protected static string $resource = ProspectResource::class;

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
                ->description('Coordonnées du prospect')
                ->icon('heroicon-o-user')
                ->schema([
                    Components\TextEntry::make('name')
                        ->label('Nom complet')
                        ->size(Components\TextEntry\TextEntrySize::Large)
                        ->weight('bold')
                        ->color('primary')
                        ->icon('heroicon-o-identification'),
                        // ->alignCenter(),
                    
                    Components\TextEntry::make('email')
                        ->label('Adresse email')
                        ->copyable()
                        ->copyMessage('Email copié !')
                        ->copyMessageDuration(1500)
                        ->icon('heroicon-o-envelope')
                        ->color('info')
                        ->placeholder('Non renseigné'),
                        // ->alignCenter(),

                    Components\TextEntry::make('phone')
                        ->label('Téléphone')
                        ->copyable()
                        ->copyMessage('Téléphone copié !')
                        ->copyMessageDuration(1500)
                        ->icon('heroicon-o-phone')
                        ->color('success')
                        ->placeholder('Non renseigné'),
                        // ->alignCenter(),
                ])
                ->columns(3)
                ->collapsible(),

            Components\Section::make('Préférences de recherche')
                ->description('Critères et exigences du prospect')
                ->icon('heroicon-o-magnifying-glass')
                ->schema([
                    Components\TextEntry::make('objet_labels')
                        ->label('Objets recherchés')
                        ->badge()
                        ->color('primary')
                        ->getStateUsing(fn($record) => $record->objet_labels ?: ['Aucun objet spécifié'])
                        ->separator(','),
                        // ->alignCenter(),
                    
                    Components\TextEntry::make('type_labels')
                        ->label('Types de biens')
                        ->badge()
                        ->color('success')
                        ->getStateUsing(fn($record) => $record->type_labels ?: ['Aucun type spécifié'])
                        ->separator(','),
                        // ->alignCenter(),
                    
                    Components\TextEntry::make('budget')
                        ->label('Budget disponible')
                        ->icon('heroicon-o-currency-euro')
                        ->color('warning')
                        ->weight('medium')
                        ->placeholder('Budget non spécifié')
                        ->copyable(),
                        // ->alignCenter(),
                    
                    Components\TextEntry::make('secteur_localisation')
                        ->label('Secteur ou localisation')
                        ->icon('heroicon-o-map-pin')
                        ->color('danger')
                        ->placeholder('Localisation non spécifiée')
                        ->copyable(),
                        // ->alignCenter(),
                ])
                ->columns(2)
                ->collapsible(),

            Components\Section::make('Résumé détaillé')
                ->description('Synthèse complète des préférences du prospect')
                ->icon('heroicon-o-document-text')
                ->schema([
                    
                    Components\TextEntry::make('preferences_string')
                        ->label('')
                        ->getStateUsing(fn($record) => $record->preferences_string ?: 'Aucune préférence détaillée disponible pour ce prospect.')
                        ->prose()
                        ->markdown()
                        ->columnSpanFull(),
                        // ->alignCenter(),
                ])
                ->collapsible(),

            Components\Section::make('Métadonnées')
                ->description('Informations de suivi et d\'historique')
                ->icon('heroicon-o-clock')
                ->schema([
                    Components\TextEntry::make('created_at')
                        ->label('Date de création')
                        ->dateTime('d/m/Y à H:i:s')
                        ->color('gray')
                        ->icon('heroicon-o-calendar-days'),
                        // ->alignCenter(),
                    
                    Components\TextEntry::make('updated_at')
                        ->label('Dernière modification')
                        ->dateTime('d/m/Y à H:i:s')
                        ->color('gray')
                        ->icon('heroicon-o-pencil-square')
                        ->since()
                        // ->alignCenter(),
                ])
                ->columns(2)
                ->collapsed()
                ->collapsible(),
        ]);
    }
}