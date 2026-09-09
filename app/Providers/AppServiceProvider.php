<?php

namespace App\Providers;

use App\Models\Gasto;
use App\Models\Producto;
use App\Models\Venta;
use App\Observers\GastoObserver;
use App\Observers\ProductoObserver;
use App\Observers\VentaObserver;
use Illuminate\Support\ServiceProvider;

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
        Producto::observe(ProductoObserver::class);
        Gasto::observe(GastoObserver::class);
    }
}
