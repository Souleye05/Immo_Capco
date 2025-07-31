<?php

namespace App\Observers;

use App\Models\Contract;
use App\Services\TenantCacheManager;

class ContractObserver
{
    /**
     * Handle the Contract "created" event.
     */
    public function created(Contract $contract): void
    {
        $this->invalidateCache($contract);
    }

    /**
     * Handle the Contract "updated" event.
     */
    public function updated(Contract $contract): void
    {
        $this->invalidateCache($contract);
    }

    /**
     * Handle the Contract "deleted" event.
     */
    public function deleted(Contract $contract): void
    {
        $this->invalidateCache($contract);
    }

    /**
     * Handle the Contract "restored" event.
     */
    public function restored(Contract $contract): void
    {
        $this->invalidateCache($contract);
    }

    /**
     * Handle the Contract "force deleted" event.
     */
    public function forceDeleted(Contract $contract): void
    {
        $this->invalidateCache($contract);
    }

    /**
     * Invalide le cache lié à ce contrat
     */
    private function invalidateCache(Contract $contract): void
    {
        TenantCacheManager::invalidateRelatedCache('Contract', $contract->id, $contract->agency_id);
    }
}
