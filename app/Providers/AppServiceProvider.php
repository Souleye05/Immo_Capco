<?php

namespace App\Providers;

use App\Repositories\ExpenseRepository;
use App\Repositories\FlatRepository;
use App\Repositories\PaymentRepository;
use App\Services\ExpenseService;
use App\Services\PaymentService;
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
}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
