<?php

namespace App\Traits;

use App\Enums\PaymentMethod;

trait PaymentMethodHelper
{
    protected static function getPaymentMethodColor(string $paymentMethod): string
    {
        try {
            return PaymentMethod::from($paymentMethod)->getColor();
        } catch (\ValueError) {
            // Si la valeur n'existe pas dans l'enum, retourner une couleur par défaut
            return 'secondary';
        }
    }

    protected static function getPaymentMethodIcon(string $paymentMethod): string
    {
        try {
            return PaymentMethod::from($paymentMethod)->getIcon();
        } catch (\ValueError) {
            // Si la valeur n'existe pas dans l'enum, retourner une icône par défaut
            return 'heroicon-o-question-currency-dollar';
        }
    }

    /**
     * Obtenir le libellé selon la méthode de paiement
     * 
     * @param string $paymentMethod Méthode de paiement
     * @return string Libellé français
     */
    protected static function getPaymentMethodLabel(string $paymentMethod): string
    {
        try {
            return PaymentMethod::from($paymentMethod)->getLabel();
        } catch (\ValueError) {
            return $paymentMethod; // Retourner la valeur originale si non trouvée
        }
    }
}