<?php

namespace App\Observers;

use App\Models\Property;
use App\Services\TenantCacheManager;

class PropertyObserver
{
    /**
     * Handle the Property "created" event.
     */
    public function created(Property $property): void
    {
        $this->invalidateCache($property);
    }

    /**
     * Handle the Property "updated" event.
     */
    public function updated(Property $property): void
    {
        $this->invalidateCache($property);
    }

    /**
     * Handle the Property "deleted" event.
     */
    public function deleted(Property $property): void
    {
        $this->invalidateCache($property);
    }

    /**
     * Handle the Property "restored" event.
     */
    public function restored(Property $property): void
    {
        $this->invalidateCache($property);
    }

    /**
     * Handle the Property "force deleted" event.
     */
    public function forceDeleted(Property $property): void
    {
        $this->invalidateCache($property);
    }

    /**
     * Invalide le cache lié à cette propriété
     */
    private function invalidateCache(Property $property): void
    {
        TenantCacheManager::invalidateRelatedCache('Property', $property->id, $property->agency_id);
    }
}
