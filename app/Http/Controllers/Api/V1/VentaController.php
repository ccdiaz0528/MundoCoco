<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class VentaController
{
    /** Ventas con detalle en un rango de fechas (por defecto, últimos 30 días). */
    public function index(Request $request): Collection
    {
        $rango = Validator::validate($request->only(['desde', 'hasta']), [
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ]);

        $query = Venta::query()->with(['metodoPago', 'detalles.producto', 'sucursal']);

        // Filtro por fecha con whereDate (SQLite guarda date como Y-m-d 00:00:00).
        $query->whereDate('fecha_venta', '>=', $rango['desde'] ?? now()->subDays(30)->toDateString());
        if (! empty($rango['hasta'])) {
            $query->whereDate('fecha_venta', '<=', $rango['hasta']);
        }

        return $query->orderBy('fecha_venta')
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
    }
}
