<?php

namespace App\Observers;

use App\Models\Versement;
use App\Models\Unsold;
use App\Models\UnsoldPaymentHistory;

class VersementObserver
{
    /**
     * Handle the Versement "created" event.
     */
    public function created(Versement $versement)
    {
        $payment = $versement->payment;

        if ($payment && $payment->isComplete()) {
            // Soft delete les impayés liés au locataire du paiement
            $payment->unsolds()->each(function ($unsold) {
                $unsold->forceDelete(); // soft delete
            });

            // (Optionnel) mettre à jour le statut du paiement comme complet (1)
            $payment->update(['status' => 1]);
        }
    }

    /**
     * Handle the Versement "updated" event.
     */
    public function updated(Versement $versement): void
    {
        //
    }

    /**
     * Handle the Versement "restored" event.
     */
    public function restored(Versement $versement): void
    {
        //
    }

    /**
     * Handle the Versement "force deleted" event.
     */
    public function forceDeleted(Versement $versement): void
    {
        //
    }
}
