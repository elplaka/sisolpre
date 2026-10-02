<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Solicitud;               // <-- Importa el modelo
use App\Observers\SolicitudObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Solicitud::observe(SolicitudObserver::class);
    }
}
