<?php

use App\Http\Controllers\Api\V1\ProductoController;
use App\Http\Controllers\Api\V1\ReporteController;
use App\Http\Controllers\Api\V1\VentaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API de integración v1 (RNF07) - solo lectura
|--------------------------------------------------------------------------
| Token Sanctum emitido con: php artisan mundococo:token-api {email}
| Cada endpoint exige además el permiso RF10 del usuario dueño del token.
*/

Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('/productos', [ProductoController::class, 'index'])->middleware('can:ver productos');
    Route::get('/ventas', [VentaController::class, 'index'])->middleware('can:ver ventas');
    Route::get('/reportes/{tipo}', [ReporteController::class, 'show'])->middleware('can:ver reportes');
});
