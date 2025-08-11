<?php

namespace App\Services;

use App\Models\Owner;
use App\Models\Property;
use App\Models\Tenant;
use Filament\Notifications\Notification;

class RemittanceValidationService
{
  /**
   * Create a standardized error notification with corrective actions
   */
  public static function createErrorNotification(string $title, string $message, array $actions = []): Notification
  {
    $notification = Notification::make()
      ->title($title)
      ->body($message)
      ->danger()
      ->persistent();

    // Add action suggestions if provided
    if (!empty($actions)) {
      $actionText = "\n\nActions suggérées:\n• " . implode("\n• ", $actions);
      $notification->body($message . $actionText);
    }

    return $notification;
  }

  /**
   * Create a warning notification with suggestions
   */
  public static function createWarningNotification(string $title, string $message, array $suggestions = []): Notification
  {
    $notification = Notification::make()
      ->title($title)
      ->body($message)
      ->warning()
      ->persistent();

    if (!empty($suggestions)) {
      $suggestionText = "\n\nSuggestions:\n• " . implode("\n• ", $suggestions);
      $notification->body($message . $suggestionText);
    }

    return $notification;
  }

  /**
   * Create a success notification with next steps
   */
  public static function createSuccessNotification(string $title, string $message, array $nextSteps = []): Notification
  {
    $notification = Notification::make()
      ->title($title)
      ->body($message)
      ->success();

    if (!empty($nextSteps)) {
      $stepsText = "\n\nProchaines étapes:\n• " . implode("\n• ", $nextSteps);
      $notification->body($message . $stepsText);
    }

    return $notification;
  }

  /**
   * Get user-friendly error message for owner validation issues
   */
  public static function getOwnerValidationMessage(Owner $owner): array
  {
    $errors = $owner->getRemittanceValidationErrors();
    $userFriendlyMessages = [];
    $actions = [];

    foreach ($errors as $error) {
      switch (true) {
        case str_contains($error, 'no associated properties'):
          $userFriendlyMessages[] = 'Ce propriétaire n\'a aucune propriété associée';
          $actions[] = 'Associer une propriété à ce propriétaire dans la section Propriétés';
          break;

        case str_contains($error, 'Primary property not found'):
          $userFriendlyMessages[] = 'La propriété principale de ce propriétaire est introuvable';
          $actions[] = 'Vérifier l\'association propriétaire-propriété dans la base de données';
          break;

        case str_contains($error, 'Property ID is missing'):
          $userFriendlyMessages[] = 'L\'identifiant de la propriété est manquant';
          $actions[] = 'Contacter l\'administrateur système pour corriger les données';
          break;

        case str_contains($error, 'not associated with an agency'):
          $userFriendlyMessages[] = 'La propriété n\'est pas associée à une agence';
          $actions[] = 'Associer la propriété à une agence dans la section Propriétés';
          break;

        case str_contains($error, 'no associated flats'):
          $userFriendlyMessages[] = 'La propriété n\'a pas d\'appartements associés';
          $actions[] = 'Ajouter des appartements à cette propriété';
          $actions[] = 'Vérifier la configuration de la propriété';
          break;

        case str_contains($error, 'commission value is not set'):
          $userFriendlyMessages[] = 'La valeur de commission n\'est pas configurée';
          $actions[] = 'Configurer la valeur de commission dans les paramètres de la propriété';
          break;

        case str_contains($error, 'commission unit is not configured'):
          $userFriendlyMessages[] = 'L\'unité de commission n\'est pas configurée';
          $actions[] = 'Configurer l\'unité de commission (pourcentage ou montant fixe)';
          break;

        case str_contains($error, 'no valid user association'):
          $userFriendlyMessages[] = 'Ce propriétaire n\'a pas d\'utilisateur associé valide';
          $actions[] = 'Associer un utilisateur à ce propriétaire';
          $actions[] = 'Vérifier les informations de contact du propriétaire';
          break;

        case str_contains($error, 'incomplete remittance'):
          $userFriendlyMessages[] = 'Ce propriétaire a des reversements incomplets';
          $actions[] = 'Finaliser les reversements en cours avant d\'en créer de nouveaux';
          $actions[] = 'Consulter l\'historique des reversements de ce propriétaire';
          break;

        default:
          $userFriendlyMessages[] = $error;
          $actions[] = 'Contacter l\'administrateur système pour assistance';
          break;
      }
    }

    return [
      'messages' => $userFriendlyMessages,
      'actions' => array_unique($actions)
    ];
  }

  /**
   * Get user-friendly error message for property validation issues
   */
  public static function getPropertyValidationMessage(Property $property): array
  {
    $messages = [];
    $actions = [];

    if (!$property->agency_id) {
      $messages[] = 'Cette propriété n\'est pas associée à une agence';
      $actions[] = 'Associer la propriété à une agence';
    }

    if ($property->flats()->count() === 0) {
      $messages[] = 'Cette propriété n\'a pas d\'appartements';
      $actions[] = 'Ajouter des appartements à cette propriété';
    }

    if (!$property->commission_value) {
      $messages[] = 'La valeur de commission n\'est pas définie';
      $actions[] = 'Configurer la valeur de commission';
    }

    if (!$property->commission_unit) {
      $messages[] = 'L\'unité de commission n\'est pas définie';
      $actions[] = 'Configurer l\'unité de commission';
    }

    return [
      'messages' => $messages,
      'actions' => $actions
    ];
  }

  /**
   * Get user-friendly error message for tenant validation issues
   */
  public static function getTenantValidationMessage(?Tenant $tenant, ?int $propertyId): array
  {
    $messages = [];
    $actions = [];

    if (!$tenant) {
      $messages[] = 'Le locataire sélectionné n\'existe pas';
      $actions[] = 'Sélectionner un locataire valide';
      $actions[] = 'Vérifier que le locataire n\'a pas été supprimé';
      return ['messages' => $messages, 'actions' => $actions];
    }

    if ($propertyId) {
      $hasActiveContract = $tenant->contracts()
        ->where('property_id', $propertyId)
        ->where('status', 'active')
        ->exists();

      if (!$hasActiveContract) {
        $messages[] = 'Ce locataire n\'a pas de contrat actif pour cette propriété';
        $actions[] = 'Vérifier le statut du contrat du locataire';
        $actions[] = 'Activer le contrat si nécessaire';
      }
    }

    return [
      'messages' => $messages,
      'actions' => $actions
    ];
  }

  /**
   * Get user-friendly error message for calculation issues
   */
  public static function getCalculationErrorMessage(string $remittanceType, array $context = []): array
  {
    $messages = [];
    $actions = [];

    switch ($remittanceType) {
      case 'loyer':
        $messages[] = 'Impossible de calculer le montant du reversement de loyer';

        if (isset($context['month']) && ($context['month'] < 1 || $context['month'] > 12)) {
          $messages[] = 'Le mois sélectionné n\'est pas valide';
          $actions[] = 'Sélectionner un mois entre 1 et 12';
        }

        if (isset($context['year']) && ($context['year'] < 2020 || $context['year'] > 2030)) {
          $messages[] = 'L\'année sélectionnée n\'est pas valide';
          $actions[] = 'Sélectionner une année entre 2020 et 2030';
        }

        $actions[] = 'Vérifier que la propriété a des paiements enregistrés pour cette période';
        $actions[] = 'Vérifier la configuration des commissions de la propriété';
        break;

      case 'caution':
        $messages[] = 'Impossible de calculer le montant du reversement de caution';
        $actions[] = 'Vérifier que le locataire a versé une caution';
        $actions[] = 'Vérifier que le contrat du locataire est actif';
        $actions[] = 'Consulter l\'historique des paiements du locataire';
        break;

      default:
        $messages[] = 'Type de reversement non reconnu';
        $actions[] = 'Sélectionner un type de reversement valide (loyer ou caution)';
        break;
    }

    return [
      'messages' => $messages,
      'actions' => $actions
    ];
  }

  /**
   * Send notification for owner validation failure
   */
  public static function notifyOwnerValidationFailure(Owner $owner): void
  {
    $validation = self::getOwnerValidationMessage($owner);

    self::createErrorNotification(
      'Propriétaire invalide pour reversement',
      'Ce propriétaire ne peut pas créer de reversement: ' . implode(', ', $validation['messages']),
      $validation['actions']
    )->send();
  }

  /**
   * Send notification for property validation failure
   */
  public static function notifyPropertyValidationFailure(Property $property): void
  {
    $validation = self::getPropertyValidationMessage($property);

    self::createErrorNotification(
      'Propriété invalide pour reversement',
      'Cette propriété ne peut pas être utilisée pour un reversement: ' . implode(', ', $validation['messages']),
      $validation['actions']
    )->send();
  }

  /**
   * Send notification for calculation failure
   */
  public static function notifyCalculationFailure(string $remittanceType, array $context = []): void
  {
    $validation = self::getCalculationErrorMessage($remittanceType, $context);

    self::createErrorNotification(
      'Erreur de calcul du reversement',
      implode(', ', $validation['messages']),
      $validation['actions']
    )->send();
  }

  /**
   * Send notification for successful calculation
   */
  public static function notifyCalculationSuccess(string $remittanceType, float $amount): void
  {
    $nextSteps = [
      'Vérifier les montants calculés',
      'Procéder à la création du reversement si tout est correct',
      'Consulter l\'historique des reversements si nécessaire'
    ];

    self::createSuccessNotification(
      'Calcul réussi',
      "Le montant du reversement de {$remittanceType} a été calculé avec succès: " . number_format($amount, 0, ',', ' ') . ' XOF',
      $nextSteps
    )->send();
  }
}
