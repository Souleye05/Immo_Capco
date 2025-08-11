<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Support\Facades\Session;

class TenantSessionManager
{
  /**
   * Set the current tenant for a user
   */
  public function setTenant(Agency $agency, User $user): void
  {
    Session::put('tenant_id', $agency->id);
    Session::put('tenant_slug', $agency->slug);
  }

  /**
   * Get the current tenant for a user
   */
  public function getCurrentTenant(User $user): ?Agency
  {
    $tenantId = Session::get('tenant_id');

    if (!$tenantId) {
      return null;
    }

    return Agency::find($tenantId);
  }

  /**
   * Clear the tenant session
   */
  public function clearTenantSession(): void
  {
    Session::forget(['tenant_id', 'tenant_slug']);
  }

  /**
   * Clean up expired sessions (placeholder implementation)
   */
  public function cleanupExpiredSessions(): void
  {
    // Placeholder implementation - in a full implementation,
    // this would clean up expired sessions from storage
  }
}
