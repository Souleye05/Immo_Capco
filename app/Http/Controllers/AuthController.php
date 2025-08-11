<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Agency;
use App\Services\AgencyPermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
  /**
   * Afficher le formulaire de connexion
   */
  public function showLoginForm()
  {
    return view('auth.login');
  }

  /**
   * Traiter la connexion
   */
  public function login(Request $request)
  {
    $request->validate([
      'email' => 'required|email',
      'password' => 'required',
    ]);

    // Tentative d'authentification
    if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
      throw ValidationException::withMessages([
        'email' => 'Ces identifiants ne correspondent pas à nos enregistrements.',
      ]);
    }

    $user = Auth::user();

    // Debug : Afficher les informations de l'utilisateur
    \Log::info('User login attempt', [
      'user_id' => $user->id,
      'email' => $user->email,
      'roles' => $user->getRoleNames()->toArray(),
    ]);

    // Vérifier si l'utilisateur est super-admin
    if ($user->hasRole('super-admin')) {
      \Log::info('Super-admin login, redirecting to super-admin panel');
      // Super-admin n'a pas besoin d'agence
      return redirect()->route('filament.super-admin.pages.dashboard');
    }

    \Log::info('Non super-admin user, checking roles and agencies');

    // Vérifier si l'utilisateur a des rôles Spatie ou des rôles d'agence
    $hasSpatiRoles = $user->getRoleNames()->isNotEmpty();
    $hasAgencyRoles = $user->agencyRoles()->exists();

    \Log::info('User roles info', [
      'spatie_roles' => $user->getRoleNames()->toArray(),
      'has_agency_roles' => $hasAgencyRoles,
      'agency_roles_count' => $user->agencyRoles()->count(),
    ]);

    // Si l'utilisateur n'a ni rôles Spatie ni rôles d'agence
    if (!$hasSpatiRoles && !$hasAgencyRoles) {
      \Log::warning('User has no roles at all');
      Auth::logout();
      throw ValidationException::withMessages([
        'email' => 'Votre compte n\'a aucun rôle assigné. Contactez votre administrateur.',
      ]);
    }

    // Pour les autres utilisateurs, déterminer automatiquement l'agence
    $accessibleAgencies = AgencyPermissionService::getAccessibleAgencies($user);

    \Log::info('Accessible agencies', [
      'count' => $accessibleAgencies->count(),
      'agencies' => $accessibleAgencies->pluck('name', 'id')->toArray(),
    ]);

    if ($accessibleAgencies->count() === 0) {
      \Log::warning('User has no accessible agencies');
      Auth::logout();
      throw ValidationException::withMessages([
        'email' => 'Vous n\'avez accès à aucune agence. Contactez votre administrateur.',
      ]);
    }

    // Sélection automatique de l'agence
    $selectedAgency = $accessibleAgencies->first();

    \Log::info('Selected agency', [
      'agency_id' => $selectedAgency->id,
      'agency_name' => $selectedAgency->name,
      'agency_slug' => $selectedAgency->slug,
    ]);

    // Stocker l'agence sélectionnée en session
    session([
      'selected_agency_id' => $selectedAgency->id,
      'selected_agency_slug' => $selectedAgency->slug,
    ]);

    // Rediriger directement vers le panel approprié
    $redirectUrl = AgencyPermissionService::getPanelUrl($user, $selectedAgency);

    \Log::info('Redirecting to panel', [
      'redirect_url' => $redirectUrl,
    ]);

    // Vérifier que l'utilisateur est toujours connecté avant la redirection
    \Log::info('User still authenticated before redirect', [
      'authenticated' => Auth::check(),
      'user_id' => Auth::id(),
    ]);

    return redirect()->to($redirectUrl);
  }

  /**
   * Vérifier si un utilisateur a accès à une agence
   */
  private function userHasAccessToAgency(User $user, Agency $agency): bool
  {
    $accessibleAgencies = AgencyPermissionService::getAccessibleAgencies($user);
    return $accessibleAgencies->contains('id', $agency->id);
  }
}
