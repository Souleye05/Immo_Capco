<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Enums\PaymentType;
use App\Filament\Resources\PaymentResource;
use App\Models\Contract;
use App\Services\FactureService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    /**
     * Validation et transformation des données avant création
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->validateRequiredFields($data);
        $this->validateContract($data);

        // Utiliser le service pour préparer les données
        try {
            $factureService = app(FactureService::class);
            return $factureService->preparePaymentData($data);
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Erreur de validation')
                ->body($e->getMessage())
                ->persistent()
                ->send();
                
            $this->halt();
            return $data;
        }
    }

    /**
     * Valide les champs requis
     */
    private function validateRequiredFields(array $data): void
    {
        if (empty($data['contract_id'])) {
            $this->sendErrorAndHalt('Veuillez sélectionner un contrat pour créer la facture.');
        }

        if (empty($data['tenant_id'])) {
            $this->sendErrorAndHalt('Veuillez sélectionner un locataire.');
        }

        if (empty($data['type'])) {
            $this->sendErrorAndHalt('Veuillez sélectionner un type de facture.');
        }

       // Validation spécifique pour les types qui requièrent un mois
        $type = PaymentType::from($data['type']);
        if ($type->requiresMonth() && empty($data['current_month'])) {
            $this->sendErrorAndHalt("Le mois est requis pour le type de facture '{$type->getLabel()}'.");
        }

        // Validation métier principale : pas de doublon pour le même type/mois/contrat
        if ($type->requiresMonth() && !empty($data['current_month'])) {
            $factureService = app(FactureService::class);
            $validation = $factureService->validatePaymentCreation(
                $type, 
                $data['contract_id'], 
                $data['current_month']
            );

            if (!$validation['can_create']) {
                $this->sendErrorAndHalt($validation['message']);
            }
        }
        

    }

    /**
     * Valide le contrat sélectionné
     */
    private function validateContract(array $data): void
    {
        $contract = Contract::find($data['contract_id']);
        
        if (!$contract) {
            $this->sendErrorAndHalt('Le contrat sélectionné n\'existe pas.');
        }
        
        if ($contract->tenant_id != $data['tenant_id']) {
            $this->sendErrorAndHalt('Le contrat sélectionné n\'appartient pas au locataire choisi.');
        }
    }

    /**
     * Envoie une notification d'erreur et arrête le processus
     */
    private function sendErrorAndHalt(string $message): void
    {
        Notification::make()
            ->danger()
            ->title('Erreur de validation')
            ->body($message)
            ->persistent()
            ->send();
            
        $this->halt();
    }

    /**
     * Actions après création réussie
     */
    protected function afterCreate(): void
    {
        $payment = $this->record;
        $contract = $payment->contract;
        
        if (!$contract) {
            return;
        }

        $contractReference = $contract->contract_number ?? "ID: {$contract->id}";
        $contractStatus = $contract->label ?? 'inconnu';
        
        // Message et type de notification selon le statut du contrat
        [$message, $notificationType] = $this->getNotificationDetails($payment, $contractReference, $contractStatus);
        
        Notification::make()
            ->{$notificationType}()
            ->title('Facture créée avec succès')
            ->body($message)
            ->send();
    }

    /**
     * Détermine le message et le type de notification
     */
    private function getNotificationDetails(Model $payment, string $contractReference, string $contractStatus): array
    {
        $baseMessage = "La facture {$payment->numero} a été créée";
        
        return match($contractStatus) {
            'active' => [
                "{$baseMessage} avec succès pour le contrat actif {$contractReference}.",
                'success'
            ],
            'expired' => [
                "{$baseMessage} pour le contrat expiré {$contractReference}. Vérifiez si c'est intentionnel.",
                'warning'
            ],
            'terminated' => [
                "{$baseMessage} pour le contrat terminé {$contractReference}. Vérifiez si c'est intentionnel.",
                'warning'
            ],
            default => [
                "{$baseMessage} pour le contrat {$contractReference} (statut: {$contractStatus}).",
                'info'
            ]
        };
    }

    /**
     * Gestion centralisée des erreurs de validation
     */
    protected function onValidationError(\Illuminate\Validation\ValidationException $exception): void
    {
        $errors = $exception->validator->errors();
        
        // Messages d'erreur spécifiques
        $errorMessages = [
            'contract_id' => 'Le champ contrat est requis. Veuillez sélectionner un contrat valide.',
            'tenant_id' => 'Veuillez sélectionner un locataire.',
            'type' => 'Veuillez sélectionner un type de facture.',
            'current_month' => 'Le mois est requis pour ce type de facture.',
        ];

        foreach ($errorMessages as $field => $message) {
            if ($errors->has($field)) {
                Notification::make()
                    ->danger()
                    ->title('Erreur de validation')
                    ->body($message)
                    ->persistent()
                    ->send();
                break; // Afficher seulement la première erreur
            }
        }

        parent::onValidationError($exception);
    }

    /**
     * Personnalisation du titre de redirection
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Actions disponibles dans l'en-tête
     */
 
}