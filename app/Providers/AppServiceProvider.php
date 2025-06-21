<?php

namespace App\Providers;

use App\Models\Payment;
use App\Models\Versement;
use App\Observers\PaymentObserver;
use App\Observers\VersementObserver;
use App\Repositories\ExpenseRepository;
use App\Repositories\FlatRepository;
use App\Repositories\PaymentRepository;
use App\Services\DocumentGeneratorService;
use App\Services\ExpenseService;
use App\Services\FactureService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\VersementService;
use Filament\Facades\Filament;
use Illuminate\Console\Scheduling\Schedule;
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
    $this->app->singleton(DocumentGeneratorService::class, DocumentGeneratorService::class);


    // Enregistrer les services
    $this->app->singleton(PaymentService::class, PaymentService::class);
    $this->app->singleton(ExpenseService::class, ExpenseService::class);
    $this->app->singleton(InvoiceService::class, InvoiceService::class);
    $this->app->singleton(VersementService::class, VersementService::class);
    $this->app->singleton(FactureService::class, FactureService::class);
}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
        //     // Définissez vos tâches planifiées ici
        //     $schedule->command('invoices:generate-monthly')->monthlyOn(1, '00:00'); // 1er du mois à 00:00
        // });
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            // Définissez vos tâches planifiées ici
            $schedule->command('payments:generate')
            // ->monthlyOn(1, '00:00'); // 1er du mois à 00:00
            ->monthlyOn(1, '08:00')
             ->timezone('Africa/Dakar');

             // Vérifier les alertes chaque jour à 8h
    $schedule->command('contracts:check-renewals')
             ->dailyAt('08:00')
             ->withoutOverlapping()
             ->timezone('Africa/Dakar');
        });
        // $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
        //     // Définissez vos tâches planifiées ici
        //     $schedule->command('payments:generate')->monthlyOn(1, '00:00'); // 1er du mois à 00:00
        // });

        
        
        // Enregistrement de l'observer pour le modèle Versement
        Versement::observe(VersementObserver::class); // Décommenter si nécessaire

       Filament::registerRenderHook(
        'panels::auth.login.form.after',
        fn(): string => view('partials.login-style')->render(),
        );
       
    }
}
