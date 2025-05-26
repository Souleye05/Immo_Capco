<?php

namespace App\Traits;

use App\Models\Versement;

trait VersementLogicHelper
{
    protected static function isSingleFullPayment(Versement $versement): bool
    {
        $payment = $versement->payment;

        if (!$payment) {
            return false;
        }

        if ($versement->amount < $payment->amount) {
            return false;
        }

        $versementCount = $payment->relationLoaded('versement')
            ? $payment->versement->count()
            : $payment->versement()->count();

        return $versementCount === 1;
    }

    protected static function getDocumentType(Versement $versement): array
    {
        $isSingleFull = static::isSingleFullPayment($versement);

        return [
            'label' => $isSingleFull ? 'Quittance' : 'Reçu',
            'icon' => $isSingleFull ? 'heroicon-o-document-check' : 'heroicon-o-document-arrow-down',
            'color' => $isSingleFull ? 'success' : 'info',
            'route' => $isSingleFull 
                ? route('documents.download-quittance', $versement->payment)
                : route('documents.download-recu', $versement)
        ];
    }
}