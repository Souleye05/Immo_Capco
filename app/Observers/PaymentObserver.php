<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\TenantCacheManager;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        $this->invalidateCache($payment);
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        $this->invalidateCache($payment);
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        $this->invalidateCache($payment);
    }

    /**
     * Handle the Payment "restored" event.
     */
    public function restored(Payment $payment): void
    {
        $this->invalidateCache($payment);
    }

    /**
     * Handle the Payment "force deleted" event.
     */
    public function forceDeleted(Payment $payment): void
    {
        $this->invalidateCache($payment);
    }

    /**
     * Invalide le cache lié à ce paiement
     */
    private function invalidateCache(Payment $payment): void
    {
        TenantCacheManager::invalidateRelatedCache('Payment', $payment->id, $payment->agency_id);
    }
}
