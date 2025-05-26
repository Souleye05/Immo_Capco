<?php

namespace App\Filament\Resources\PaymentResource\RelationManagers;

use App\Enums\PaymentMethod;
use App\Models\Versement;
use App\Services\VersementService;
use App\Traits\PaymentMethodHelper;
use App\Traits\VersementLogicHelper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class VersementRelationManager extends RelationManager
{
    use PaymentMethodHelper, VersementLogicHelper;

    protected static string $relationship = 'versement';
    protected static ?string $recordTitleAttribute = 'reference';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('reference')
                ->label('Référence du versement')
                ->default(fn() => 'VER-' . random_int(100000, 999999))
                ->disabled()
                ->dehydrated()
                ->required(),

            Forms\Components\TextInput::make('amount')
                ->label('Montant')
                ->numeric()
                ->minValue(1)
                ->required(),
            
            Forms\Components\Select::make('payment_method')
                ->label('Méthode de paiement')
                ->searchable()
                ->options(PaymentMethod::getOptions())
                ->required(),

            Forms\Components\DatePicker::make('versement_date')
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
                    ->formatStateUsing(fn ($state): string => number_format($state, 0, ',', ' ') . ' FCFA'),
                    
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Mode de versement')
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => static::getPaymentMethodColor($state)) // <- Nouvelle ligne
                    ->icon(fn ($state) => static::getPaymentMethodIcon($state))   // <- Nouvelle ligne
                    ->formatStateUsing(fn ($state) => static::getPaymentMethodLabel($state)), // <- Nouvelle ligne   
                    
                Tables\Columns\TextColumn::make('versement_date')
                    ->label('Date de paiement')
                    ->alignCenter()
                    ->date(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Créer un versement')
                    ->before(fn($data, $livewire, $action) => $this->validateVersementCreation($data, $livewire, $action))
                    ->after(fn($livewire, $record) => $this->handleVersementCreated($livewire, $record)),
            ])
            ->actions([
                Tables\Actions\Action::make('download_document')
                    ->label(fn (Versement $record) => static::getDocumentType($record)['label'])
                    ->icon(fn (Versement $record) => static::getDocumentType($record)['icon'])
                    ->color(fn (Versement $record) => static::getDocumentType($record)['color'])
                    ->url(fn (Versement $record) => static::getDocumentType($record)['route'])
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),
                
                Tables\Actions\DeleteAction::make()
                    ->after(fn($record, $livewire) => $this->handleVersementDeleted($record, $livewire)),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->after(fn($records, $livewire) => $this->handleBulkVersementDeleted($records, $livewire)),
            ]);
    }

    protected function getVersementService(): VersementService
    {
        return app(VersementService::class);
    }

    private function validateVersementCreation(array $data, RelationManager $livewire, Tables\Actions\CreateAction $action): bool
    {
        try {
            $this->getVersementService()->validateVersementCreation(
                $livewire->ownerRecord,
                $data['amount']
            );
            return true;
        } catch (\InvalidArgumentException $e) {
            $this->showWarningNotification('Opération impossible', $e->getMessage());
            $action->cancel();
            return false;
        }
    }

    private function handleVersementCreated($livewire, $record): void
    {
        try {
            $result = $this->getVersementService()->processVersementCreated($record['payment_id']);
            $this->showSuccessNotification($result['title'], $result['message']);
        } catch (\Exception $e) {
            $this->showErrorNotification('Erreur', 'Une erreur est survenue lors du traitement');
        }
        
        $livewire->dispatch('refresh');
    }

    private function handleVersementDeleted($record, $livewire): void
    {
        $this->getVersementService()->handleVersementDeleted($record->payment_id);
        $this->showSuccessNotification('Versement supprimé', 'Le statut de la facture a été mis à jour');
        $livewire->dispatch('refresh');
    }

    private function handleBulkVersementDeleted($records, $livewire): void
    {
        $paymentIds = $records->pluck('payment_id')->unique();
        
        foreach ($paymentIds as $paymentId) {
            $this->getVersementService()->handleVersementDeleted($paymentId);
        }
        
        $this->showSuccessNotification('Versements supprimés', 'Les statuts des factures ont été mis à jour');
        $livewire->dispatch('refresh');
    }

    private function showSuccessNotification(string $title, string $body): void
    {
        Notification::make()->success()->title($title)->body($body)->send();
    }

    private function showWarningNotification(string $title, string $body): void
    {
        Notification::make()->warning()->title($title)->body($body)->send();
    }

    private function showErrorNotification(string $title, string $body): void
    {
        Notification::make()->error()->title($title)->body($body)->send();
    }
}