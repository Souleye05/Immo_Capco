<?php

use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('/welcome');
});



// Route::redirect('/', '/admin/login');

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