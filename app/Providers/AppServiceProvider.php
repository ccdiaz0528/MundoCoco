<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Venta;          // ← sin esto, no encuentra Venta
use App\Observers\VentaObserver; // ← sin esto, no encuentra el Observer

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
        Venta::observe(VentaObserver::class);
    }
}
