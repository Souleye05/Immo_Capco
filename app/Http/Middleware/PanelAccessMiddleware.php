<?php

namespace App\Http\Middleware;

use App\Services\AgencyPermissionService;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PanelAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        \Log::info('PanelAccessMiddleware - Request info', [
            'path' => $request->path(),
            'user_authenticated' => $user ? true : false,
            'user_id' => $user?->id,
        ]);

        if (!$user) {
            \Log::info('PanelAccessMiddleware - No user, redirecting to login');
            return redirect()->route('login');
        }

        $currentPanel = Filament::getCurrentPanel();
        $panelId = $currentPanel?->getId();

        \Log::info('PanelAccessMiddleware - Panel info', [
            'panel_id' => $panelId,
            'has_tenancy' => $currentPanel?->hasTenancy(),
        ]);

        // Permettre l'accès aux panels de login et de redirection
        if (in_array($panelId, ['login', 'redirection'])) {
            \Log::info('PanelAccessMiddleware - Login/redirection panel, allowing access');
            return $next($request);
        }

        // Obtenir l'agence courante pour les panels avec tenant
        $agency = null;
        if ($currentPanel?->hasTenancy()) {
            $agency = Filament::getTenant();
            \Log::info('PanelAccessMiddleware - Tenant info', [
                'tenant_id' => $agency?->id,
                'tenant_slug' => $agency?->slug,
            ]);
        }

        // Si le panel nécessite un tenant mais qu'aucun n'est défini, 
        // laisser Filament gérer la sélection de tenant
        if ($currentPanel?->hasTenancy() && !$agency) {
            \Log::info('PanelAccessMiddleware - Panel needs tenant but none set, letting Filament handle it');
            return $next($request);
        }

        // Vérifier l'accès au panel
        $canAccess = AgencyPermissionService::canAccessPanel($user, $panelId, $agency);
        \Log::info('PanelAccessMiddleware - Access check', [
            'can_access' => $canAccess,
            'panel_id' => $panelId,
        ]);

        if (!$canAccess) {
            \Log::info('PanelAccessMiddleware - Access denied, redirecting');
            // Rediriger vers le panel approprié
            if ($agency) {
                $redirectUrl = AgencyPermissionService::getPanelUrl($user, $agency);
            } else {
                $redirectUrl = route('login');
            }

            return redirect()->to($redirectUrl)->with('error', 'Vous n\'avez pas accès à ce panel.');
        }

        // Vérifier l'accès à l'agence pour les panels avec tenant
        if ($agency && !$this->userHasAccessToAgency($user, $agency)) {
            \Log::info('PanelAccessMiddleware - No access to agency, redirecting to login');
            return redirect()->route('login')
                ->with('error', 'Vous n\'avez pas accès à cette agence.');
        }

        \Log::info('PanelAccessMiddleware - Access granted, continuing');

        return $next($request);
    }

    /**
     * Vérifier si un utilisateur a accès à une agence spécifique
     *
     * @param  mixed  $user
     * @param  mixed  $agency
     * @return bool
     */
    private function userHasAccessToAgency($user, $agency): bool
    {
        // Utiliser AgencyPermissionService pour vérifier l'accès
        $accessibleAgencies = AgencyPermissionService::getAccessibleAgencies($user);
        return $accessibleAgencies->contains('id', $agency->id);
    }
}
