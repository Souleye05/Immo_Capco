<?php

namespace App\Providers;

use App\Models\Payment;
use App\Models\Versement;
use App\Observers\PaymentObserver;
use App\Observers\VersementObserver;
use App\Repositories\ExpenseRepository;
use App\Repositories\FlatRepository;
use App\Repositories\PaymentRepository;
use App\Services\ExpenseService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Enregistrer les repositories
    $this->app->singleton(FlatRepository::class, FlatRepository::class);
    $this->app->singleton(PaymentRepository::class, PaymentRepository::class);
    $this->app->singleton(ExpenseRepository::class, ExpenseRepository::class);

    // Enregistrer les services
    $this->app->singleton(PaymentService::class, PaymentService::class);
    $this->app->singleton(ExpenseService::class, ExpenseService::class);
    $this->app->singleton(InvoiceService::class, InvoiceService::class);
}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        
        // Enregistrement de l'observer pour le modèle Versement
        Versement::observe(VersementObserver::class); // Décommenter si nécessaire

Filament::registerRenderHook(
    'panels::auth.login.form.after',
    fn(): string => view('partials.login-style')->render(),
);
;  

       
    }
}
