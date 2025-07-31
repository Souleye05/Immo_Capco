<?php

namespace App\Http\Controllers;

use App\Services\TenantUserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class TenantActivationController extends Controller
{
    protected $tenantUserService;

    public function __construct(TenantUserService $tenantUserService)
    {
        $this->tenantUserService = $tenantUserService;
    }

    /**
     * Affiche le formulaire d'activation du compte
     */
    public function showActivationForm(Request $request)
    {
        $email = $request->get('email');
        $token = $request->get('token');
        $agency = $request->get('agency');

        // Vérifier que tous les paramètres sont présents
        if (!$email || !$token || !$agency) {
            return view('tenant.activation-error', [
                'error' => 'Lien d\'activation invalide. Veuillez contacter votre agence.'
            ]);
        }

        // Vérifier la signature du lien (sécurité)
        if (!$request->hasValidSignature()) {
            return view('tenant.activation-error', [
                'error' => 'Ce lien d\'activation a expiré. Veuillez contacter votre agence pour recevoir un nouveau lien.'
            ]);
        }

        // Récupérer les informations de l'agence
        $agencyInfo = \App\Models\Agency::find($agency);

        if (!$agencyInfo) {
            return view('tenant.activation-error', [
                'error' => 'Agence introuvable. Veuillez contacter le support.'
            ]);
        }

        return view('tenant.activate', [
            'email' => $email,
            'token' => $token,
            'agency' => $agencyInfo,
        ]);
    }

    /**
     * Traite l'activation du compte
     */
    public function activate(Request $request)
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ],
        ], [
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.letters' => 'Le mot de passe doit contenir au moins une lettre.',
            'password.mixed_case' => 'Le mot de passe doit contenir au moins une majuscule et une minuscule.',
            'password.numbers' => 'Le mot de passe doit contenir au moins un chiffre.',
            'password.symbols' => 'Le mot de passe doit contenir au moins un symbole.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->except('password', 'password_confirmation'));
        }

        // Tenter d'activer le compte
        $activated = $this->tenantUserService->activateTenantAccount(
            $request->email,
            $request->token,
            $request->password
        );

        if (!$activated) {
            return back()
                ->withErrors(['token' => 'Le lien d\'activation est invalide ou a expiré.'])
                ->withInput($request->except('password', 'password_confirmation'));
        }

        // Rediriger vers la page de succès
        return redirect()->route('tenant.activation.success');
    }

    /**
     * Affiche la page de succès après activation
     */
    public function activationSuccess()
    {
        return view('tenant.activation-success');
    }
}
