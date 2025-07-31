<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Filament\Facades\Filament;

class PanelAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $panel = null): Response
    {
        $user = auth()->user();

        // If no user is authenticated, let the auth middleware handle it
        if (!$user) {
            return $next($request);
        }

        // Get the current panel from Filament or use the parameter
        $currentPanel = $panel ? Filament::getPanel($panel) : Filament::getCurrentPanel();

        if (!$currentPanel) {
            return $next($request);
        }

        // Check if user can access this panel
        if (!$user->canAccessPanel($currentPanel)) {
            throw new AccessDeniedHttpException(
                "Accès refusé au panel {$currentPanel->getId()}. Vous n'avez pas les permissions nécessaires."
            );
        }

        return $next($request);
    }
}
