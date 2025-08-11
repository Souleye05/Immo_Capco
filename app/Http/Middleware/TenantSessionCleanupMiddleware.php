<?php

namespace App\Http\Middleware;

use App\Services\TenantSessionManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TenantSessionCleanupMiddleware
{
  // public function __construct(
  //   private TenantSessionManager $tenantSessionManager
  // ) {}

  /**
   * Handle an incoming request.
   *
   * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
   */
  // public function handle(Request $request, Closure $next): Response
  // {
  //   // Clean up expired sessions for authenticated users
  //   if (Auth::check()) {
  //     $this->tenantSessionManager->cleanupExpiredSessions();
  //   }

  //   $response = $next($request);

  //   // Handle logout cleanup
  //   if ($this->isLogoutRequest($request)) {
  //     $this->handleLogoutCleanup();
  //   }

  //   return $response;
  // }

  /**
   * Check if this is a logout request
   */
  // private function isLogoutRequest(Request $request): bool
  // {
  //   $path = $request->path();

  //   // Check for various logout paths
  //   $logoutPaths = [
  //     'logout',
  //     'login/logout',
  //     'admin/logout',
  //     'owner/logout',
  //     'tenant/logout',
  //     'super-admin/logout',
  //   ];

  //   foreach ($logoutPaths as $logoutPath) {
  //     if (str_contains($path, $logoutPath)) {
  //       return true;
  //     }
  //   }

  //   return false;
  // }

  /**
   * Handle cleanup when user logs out
   */
  // private function handleLogoutCleanup(): void
  // {
  //   try {
  //     // Clear tenant session
  //     $this->tenantSessionManager->clearTenantSession();

  //     Log::info("Tenant session cleaned up on logout");
  //   } catch (\Exception $e) {
  //     Log::error("Failed to cleanup tenant session on logout: {$e->getMessage()}");
  //   }
  // }
}
