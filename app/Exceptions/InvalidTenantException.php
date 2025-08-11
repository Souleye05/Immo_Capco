<?php

namespace App\Exceptions;

use Exception;

class InvalidTenantException extends Exception
{
  public function __construct(string $message = 'Invalid or inaccessible tenant selected')
  {
    parent::__construct($message);
  }

  /**
   * Get user-friendly error message
   */
  public function getUserMessage(): string
  {
    return 'L\'agence sélectionnée n\'est pas valide ou vous n\'y avez pas accès.';
  }

  /**
   * Get actionable guidance for the user
   */
  public function getActionableGuidance(): string
  {
    return 'Veuillez sélectionner une agence valide dans la liste ou contactez votre administrateur si le problème persiste.';
  }

  /**
   * Get error context for logging
   */
  public function getLogContext(): array
  {
    return [
      'error_type' => 'invalid_tenant',
      'user_action' => 'contact_admin_or_retry',
      'severity' => 'error'
    ];
  }
}
