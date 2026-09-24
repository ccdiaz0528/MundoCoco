<?php

use App\Models\Producto;
use App\Models\Venta;
use App\Services\ReporteExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

/*
|--------------------------------------------------------------------------
| API de integración v1 (RNF07) - solo lectura
|--------------------------------------------------------------------------
| Token Sanctum emitido con: php artisan mundococo:token-api {email}
| Cada endpoint exige además el permiso RF10 del usuario dueño del token.
*/

Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('/productos', fn () => Producto::query()
        ->with('categoria')
        ->where('activo', true)
        ->orderBy('nombre')
        ->get()
        ->map(fn (Producto $p) => [
            'id' => $p->id,
            'codigo' => $p->codigo,
            'nombre' => $p->nombre,
            'categoria' => $p->categoria?->nombre,
            'precio_venta' => (string) $p->precio_venta,
            'stock_actual' => $p->stock_actual,
            'stock_minimo' => $p->stock_minimo,
            'stock_bajo' => $p->stockBajo(),
        ]))->middleware('can:ver productos');

    Route::get('/ventas', function (Request $request) {
        $rango = Validator::validate($request->only(['desde', 'hasta']), [
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
        ]);

        return Venta::query()
            ->with(['metodoPago', 'detalles.producto', 'sucursal'])
            ->whereBetween('fecha_venta', [
                ($rango['desde'] ?? now()->subDays(30)->toDateString()).' 00:00:00',
                ($rango['hasta'] ?? now()->toDateString()).' 23:59:59',
            ])
            ->orderBy('fecha_venta')
            ->get()
            ->map(fn (Venta $v) => [
                'id' => $v->id,
                'fecha_venta' => $v->fecha_venta->toIso8601String(),
                'sucursal' => $v->sucursal?->nombre,
                'metodo_pago' => $v->metodoPago?->nombre,
                'total' => (string) $v->total,
                'anulada' => $v->anulada(),
                'detalles' => $v->detalles->map(fn ($d) => [
                    'producto' => $d->producto?->nombre,
                    'cantidad' => $d->cantidad,
                    'precio_unitario' => (string) $d->precio_unitario,
                    'subtotal' => (string) $d->subtotal,
                ]),
            ]);
    })->middleware('can:ver ventas');

    // Cualquier reporte de RF08/RF09/RF11 en JSON (mismos datos que PDF/Excel).
    Route::get('/reportes/{tipo}', function (Request $request, string $tipo, ReporteExportService $exportador) {
        abort_unless(array_key_exists($tipo, ReporteExportService::TIPOS), 404);
        $rango = Validator::validate($request->only(['desde', 'hasta']), [
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
        ]);
        [$encabezados, $filas] = $exportador->datos($tipo, $rango['desde'] ?? now()->subDays(30)->toDateString(), $rango['hasta'] ?? now()->toDateString());

        return [
            'reporte' => ReporteExportService::TIPOS[$tipo],
            'columnas' => $encabezados,
            'filas' => $filas,
        ];
    })->middleware('can:ver reportes');
});
