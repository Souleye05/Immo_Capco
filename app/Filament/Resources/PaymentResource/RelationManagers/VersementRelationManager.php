<?php

namespace App\Filament\Resources\PaymentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use App\Models\Versement;
use App\Services\VersementService;

class VersementRelationManager extends RelationManager
{
    protected static string $relationship = 'versement';
    protected static ?string $recordTitleAttribute = 'reference';

    protected VersementService $versementService;

    public function __construct()
    {
        $this->versementService = app(VersementService::class);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('reference')
                ->label('Référence du versement')
                ->default('VER-' . random_int(100000, 999999))
                ->disabled()
                ->dehydrated(true)
                ->required(),

            Forms\Components\TextInput::make('amount')
                ->label('Montant')
                ->numeric()
                ->minValue(1)
                ->required(),
            
            Select::make('payment_method')
                ->label('Méthode de paiement')
                ->searchable()
                ->options([
                    'OM' => 'OM',
                    'Wave' => 'Wave',
                    'Free Money' => 'Free Money',
                    'Chèque' => 'Chèque',
                    'Virement' => 'Virement',
                    'Espèces' => 'Espèces',
                ]),

            DatePicker::make('versement_date')
                ->label('Date du paiement')
                ->default(now())
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant versé')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state): string => number_format($state, 0, ',', ' ') . ' FCFA')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Mode de versement')
                    ->alignCenter()
                    ->badge()
                    ->color($this->getPaymentMethodColor(...))
                    ->icon($this->getPaymentMethodIcon(...)),
                    
                Tables\Columns\TextColumn::make('versement_date')
                    ->label('Date de paiement')
                    ->alignCenter()
                    ->date(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Créer un versement')
                    ->before($this->validateVersementCreation(...))
                    ->after($this->handleVersementCreated(...)),
            ])
            ->actions([
                // Bouton pour télécharger le reçu (toujours disponible pour chaque versement)
                Tables\Actions\Action::make('download_recu')
                    ->label('Reçu')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->url(fn (Versement $record) => route('documents.download-recu', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),
                
                Tables\Actions\DeleteAction::make()
                    ->after($this->handleVersementDeleted(...)),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->after($this->handleBulkVersementDeleted(...)),
            ]);
    }

    /**
     * Valider la création d'un versement
     */
    private function validateVersementCreation(array $data, RelationManager $livewire, Tables\Actions\CreateAction $action): bool
    {
        try {
            $this->versementService->validateVersementCreation(
                $livewire->ownerRecord,
                $data['amount']
            );
            return true;
        } catch (\InvalidArgumentException $e) {
            Notification::make()
                ->warning()
                ->title('Opération impossible')
                ->body($e->getMessage())
                ->send();
            
            $action->cancel();
            return false;
        }
    }

    /**
     * Gérer les actions après création d'un versement
     */
    private function handleVersementCreated($livewire, $record): void
    {
        try {
            $result = $this->versementService->processVersementCreated($record['payment_id']);
            
            Notification::make()
                ->success()
                ->title($result['title'])
                ->body($result['message'])
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->error()
                ->title('Erreur')
                ->body('Une erreur est survenue lors du traitement')
                ->send();
        }
        
        $livewire->dispatch('refresh');
    }

    /**
     * Gérer la suppression d'un versement
     */
    private function handleVersementDeleted($record, $livewire): void
    {
        $this->versementService->handleVersementDeleted($record->payment_id);
        
        Notification::make()
            ->success()
            ->title('Versement supprimé')
            ->body('Le statut de la facture a été mis à jour')
            ->send();
            
        $livewire->dispatch('refresh');
    }

    /**
     * Gérer la suppression en masse de versements
     */
    private function handleBulkVersementDeleted($records, $livewire): void
    {
        $paymentIds = $records->pluck('payment_id')->unique();
        
        foreach ($paymentIds as $paymentId) {
            $this->versementService->handleVersementDeleted($paymentId);
        }
        
        Notification::make()
            ->success()
            ->title('Versements supprimés')
            ->body('Les statuts des factures ont été mis à jour')
            ->send();
            
        $livewire->dispatch('refresh');
    }

    /**
     * Obtenir la couleur du badge selon la méthode de paiement
     */
    private function getPaymentMethodColor(string $state): string
    {
        return match ($state) {
            'OM' => 'danger',
            'Wave' => 'info',
            'Free Money' => 'warning',
            'Chèque' => 'warning',
            'Virement' => 'info',
            'Espèces' => 'success',
            default => 'secondary',
        };
    }

    /**
     * Obtenir l'icône selon la méthode de paiement
     */
    private function getPaymentMethodIcon(string $state): string
    {
        return match ($state) {
            'OM', 'Wave', 'Free Money' => 'heroicon-o-device-phone-mobile',
            'Chèque' => 'heroicon-o-document-text',
            'Virement' => 'heroicon-o-building-library',
            'Espèces' => 'heroicon-o-banknotes',
            default => 'heroicon-o-currency-dollar',
        };
    }
}