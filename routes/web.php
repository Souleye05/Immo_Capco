<?php

use App\Http\Controllers\ContractPdfController;
use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

// Route de connexion personnalisée pour éviter les conflits avec Filament
Route::get('/login', [App\Http\Controllers\AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [App\Http\Controllers\AuthController::class, 'login'])->name('login.submit');

// Redirections pour maintenir la compatibilité avec les anciennes URLs de connexion
Route::redirect('/super-admin/login', '/login');
Route::redirect('/admin/login', '/login');
Route::redirect('/owner/login', '/login');
Route::redirect('/tenant/login', '/login');

// Route racine qui affiche la page welcome
Route::get('/', function () {
    return view('welcome');
});

// Ancienne route de bienvenue (commentée car remplacée par la redirection)
// Route::get('/', function () {
//     return view('/welcome');
// });

// Dans routes/web.php
// Route::middleware(['auth'])->group(function () {
//     // Routes pour les téléchargements de documents
//     Route::get('/payments/{payment}/quittance', [DocumentController::class, 'downloadPaymentQuittance'])
//         ->name('payments.download-quittance');

//     Route::get('/versements/{versement}/quittance', [DocumentController::class, 'downloadVersementQuittance'])
//         ->name('versements.download-quittance');

//     Route::get('/versements/{versement}/recu', [DocumentController::class, 'downloadVersementRecu'])
//         ->name('versements.download-recu');
// });


Route::prefix('documents')->name('documents.')->group(function () {

    // Télécharger un reçu pour un versement partiel
    Route::get('/recu/{versement}', [DocumentController::class, 'downloadRecu'])
        ->name('download-recu');

    // Télécharger une quittance pour un paiement complet
    Route::get('/quittance/{payment}', [DocumentController::class, 'downloadQuittance'])
        ->name('download-quittance');

    // Télécharger une quittance détaillée depuis un versement
    Route::get('/quittance-versement/{versement}', [DocumentController::class, 'downloadQuittanceFromVersement'])
        ->name('download-quittance-from-versement');
});

Route::get('/contracts/{contract}/pdf', [ContractPdfController::class, 'generatePdf'])
    ->name('contracts.pdf');

Route::get('/contracts/{contract}/pdf/view', [ContractPdfController::class, 'viewPdf'])
    ->name('contracts.pdf.view');

Route::get('/documents/facture/{payment}', [DocumentController::class, 'downloadFacture'])
    ->name('documents.download-facture');


Route::get('/payments/{payment}/receipt', [DocumentController::class, 'downloadRecu'])
    ->name('payments.receipt')
    ->middleware(['auth']);

// Route temporaire pour forcer la déconnexion et nettoyer les sessions
Route::get('/force-logout', function () {
    \Illuminate\Support\Facades\Auth::logout();
    \Illuminate\Support\Facades\Session::flush();
    \Illuminate\Support\Facades\Session::regenerate();

    // Nettoyer les cookies Filament
    $response = redirect('/login/login');
    $response->withCookie(cookie()->forget('filament_session'));
    $response->withCookie(cookie()->forget('laravel_session'));

    return $response->with('message', 'Déconnexion forcée effectuée. Vous pouvez maintenant vous reconnecter.');
});

// Routes pour l'activation des comptes locataires
Route::prefix('tenant')->name('tenant.')->group(function () {
    Route::get('/activate', [App\Http\Controllers\TenantActivationController::class, 'showActivationForm'])
        ->name('activate');

    Route::post('/activate', [App\Http\Controllers\TenantActivationController::class, 'activate'])
        ->name('activate.submit');

    Route::get('/activation-success', [App\Http\Controllers\TenantActivationController::class, 'activationSuccess'])
        ->name('activation.success');
});
