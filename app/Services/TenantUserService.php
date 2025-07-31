<?php

namespace App\Services;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class TenantUserService
{
  /**
   * Trouve ou crée un utilisateur pour un tenant
   */
  public function findOrCreateUserForTenant(string $email, string $name, int $agencyId): User
  {
    // Chercher un utilisateur existant avec cet email
    $user = User::where('email', $email)->first();

    if ($user) {
      // L'utilisateur existe, vérifier s'il est déjà associé à cette agence
      $existingAssociation = $user->agencys()->where('agency_id', $agencyId)->first();

      if (!$existingAssociation) {
        // Associer l'utilisateur à la nouvelle agence
        $user->agencys()->attach($agencyId, [
          'created_at' => now(),
          'updated_at' => now(),
        ]);
      }

      // S'assurer que l'utilisateur a le rôle tenant
      if (!$user->hasRole('tenant')) {
        $user->assignRole('tenant');
      }

      return $user;
    }

    // L'utilisateur n'existe pas, le créer avec un compte non activé
    $user = User::create([
      'name' => $name,
      'email' => $email,
      'password' => Hash::make('password'), // Mot de passe par défaut
      'email_verified_at' => now(), // Compte activé par défaut
    ]);

    // Assigner le rôle tenant
    $user->assignRole('tenant');

    // Associer à l'agence
    $user->agencys()->attach($agencyId, [
      'created_at' => now(),
      'updated_at' => now(),
    ]);

    // Envoyer l'invitation par email
    $this->sendTenantInvitation($user, $agencyId);

    return $user;
  }

  /**
   * Associe un utilisateur existant à un tenant
   */
  public function associateUserWithTenant(User $user, Tenant $tenant): void
  {
    $tenant->update(['user_id' => $user->id]);
  }

  /**
   * Crée un tenant avec un utilisateur associé
   */
  public function createTenantWithUser(array $tenantData, string $email): Tenant
  {
    $agencyId = $tenantData['agency_id'] ?? \Filament\Facades\Filament::getTenant()?->getKey();

    if (!$agencyId) {
      throw new \Exception('Agency ID is required');
    }

    // Trouver ou créer l'utilisateur
    $user = $this->findOrCreateUserForTenant($email, $tenantData['name'], $agencyId);

    // Créer le tenant
    $tenant = Tenant::create(array_merge($tenantData, [
      'user_id' => $user->id,
      'agency_id' => $agencyId,
      'email' => $email,
    ]));

    return $tenant;
  }

  /**
   * Envoie une invitation par email au locataire
   */
  private function sendTenantInvitation(User $user, int $agencyId): void
  {
    // Générer un token d'activation sécurisé
    $token = Str::random(64);

    // Stocker le token dans la base de données (vous pouvez créer une table password_resets ou utiliser une autre méthode)
    \DB::table('password_reset_tokens')->updateOrInsert(
      ['email' => $user->email],
      [
        'token' => Hash::make($token),
        'created_at' => now(),
      ]
    );

    // Générer le lien d'activation
    $activationUrl = URL::temporarySignedRoute(
      'tenant.activate',
      now()->addDays(7), // Le lien expire dans 7 jours
      [
        'email' => $user->email,
        'token' => $token,
        'agency' => $agencyId,
      ]
    );

    // Récupérer les informations de l'agence
    $agency = \App\Models\Agency::find($agencyId);

    // Envoyer l'email d'invitation
    try {
      Mail::send('emails.tenant-invitation', [
        'user' => $user,
        'agency' => $agency,
        'activationUrl' => $activationUrl,
      ], function ($message) use ($user, $agency) {
        $message->to($user->email, $user->name)
          ->subject("Invitation - Accès à votre espace locataire ({$agency->name})");
      });
    } catch (\Exception $e) {
      // Log l'erreur mais ne pas faire échouer la création du compte
      \Log::warning('Failed to send tenant invitation email', [
        'user_id' => $user->id,
        'email' => $user->email,
        'agency_id' => $agencyId,
        'error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Active le compte d'un locataire avec un nouveau mot de passe
   */
  public function activateTenantAccount(string $email, string $token, string $password): bool
  {
    // Vérifier le token
    $resetRecord = \DB::table('password_reset_tokens')
      ->where('email', $email)
      ->first();

    if (!$resetRecord || !Hash::check($token, $resetRecord->token)) {
      return false;
    }

    // Vérifier que le token n'est pas expiré (7 jours)
    if (now()->diffInDays($resetRecord->created_at) > 7) {
      return false;
    }

    // Trouver l'utilisateur
    $user = User::where('email', $email)->first();
    if (!$user) {
      return false;
    }

    // Activer le compte
    $user->update([
      'password' => Hash::make($password),
      'email_verified_at' => now(),
    ]);

    // Supprimer le token utilisé
    \DB::table('password_reset_tokens')->where('email', $email)->delete();

    return true;
  }

  /**
   * Renvoie une invitation à un locataire
   */
  public function resendTenantInvitation(User $user, int $agencyId): void
  {
    // Vérifier que l'utilisateur n'est pas déjà activé
    if ($user->email_verified_at) {
      throw new \Exception('Le compte est déjà activé');
    }

    $this->sendTenantInvitation($user, $agencyId);
  }

  /**
   * Génère un email à partir du nom si aucun email n'est fourni
   */
  public function generateEmailFromName(string $name): string
  {
    $baseEmail = Str::slug($name, '') . '@tenant.local';
    $counter = 1;
    $email = $baseEmail;

    while (User::where('email', $email)->exists()) {
      $email = Str::slug($name, '') . $counter . '@tenant.local';
      $counter++;
    }

    return $email;
  }
}
