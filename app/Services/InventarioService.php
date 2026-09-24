<?php

namespace App\Services;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventarioService
{
    /**
     * RF02: Registrar inventario inicial - trazable
     */
    public function registrarInicial(Producto $producto, int $cantidad, ?string $observaciones = null): MovimientoInventario
    {
        return DB::transaction(function () use ($producto, $cantidad, $observaciones) {
            $producto = Producto::query()->lockForUpdate()->findOrFail($producto->id);
            $anterior = $producto->stock_actual;
            $nuevo = $cantidad;

            if ($cantidad < 0) {
                throw ValidationException::withMessages(['cantidad' => 'La cantidad inicial no puede ser negativa.']);
            }

            $producto->updateQuietly(['stock_actual' => $nuevo]);

            return MovimientoInventario::create([
                'producto_id' => $producto->id,
                'user_id' => Auth::id(),
                'tipo' => MovimientoInventario::TIPO_INICIAL,
                'cantidad' => $cantidad,
                'stock_anterior' => $anterior,
                'stock_nuevo' => $nuevo,
                'motivo' => 'Inventario inicial',
                'observaciones' => $observaciones,
            ]);
        });
    }

    /**
     * RF03: Adiciones - compras, devoluciones, ajustes positivos
     */
    public function adicionarStock(Producto $producto, int $cantidad, string $tipo, ?string $motivo = null, ?string $observaciones = null): MovimientoInventario
    {
        $tiposPermitidos = [
            MovimientoInventario::TIPO_COMPRA,
            MovimientoInventario::TIPO_DEVOLUCION,
            MovimientoInventario::TIPO_AJUSTE_POSITIVO,
        ];

        if (! in_array($tipo, $tiposPermitidos, true)) {
            throw ValidationException::withMessages(['tipo' => 'Tipo de entrada no válido. Use: compra, devolucion, ajuste_positivo.']);
        }

        if ($cantidad <= 0) {
            throw ValidationException::withMessages(['cantidad' => 'La cantidad debe ser positiva y mayor a cero.']); // RF03 regla
        }

        return DB::transaction(function () use ($producto, $cantidad, $tipo, $motivo, $observaciones) {
            $producto = Producto::query()->lockForUpdate()->findOrFail($producto->id);
            $anterior = $producto->stock_actual;
            $nuevo = $anterior + $cantidad;

            // Query builder (sin eventos): el movimiento ya se registra abajo
            // y el observer solo debe trazar ajustes manuales del panel.
            Producto::query()->whereKey($producto->id)->increment('stock_actual', $cantidad);

            return MovimientoInventario::create([
                'producto_id' => $producto->id,
                'user_id' => Auth::id(),
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'stock_anterior' => $anterior,
                'stock_nuevo' => $nuevo,
                'motivo' => $motivo ?? $tipo,
                'observaciones' => $observaciones,
            ]);
        });
    }

    /**
     * Ajuste negativo / merma
     */
    public function retirarStock(Producto $producto, int $cantidad, string $tipo, ?string $motivo = null, ?string $observaciones = null): MovimientoInventario
    {
        $tiposPermitidos = [
            MovimientoInventario::TIPO_AJUSTE_NEGATIVO,
            MovimientoInventario::TIPO_MERMA,
        ];

        if (! in_array($tipo, $tiposPermitidos, true)) {
            throw ValidationException::withMessages(['tipo' => 'Tipo de salida no válido.']);
        }

        if ($cantidad <= 0) {
            throw ValidationException::withMessages(['cantidad' => 'La cantidad debe ser positiva.']);
        }

        return DB::transaction(function () use ($producto, $cantidad, $tipo, $motivo, $observaciones) {
            $producto = Producto::query()->lockForUpdate()->findOrFail($producto->id);
            $anterior = $producto->stock_actual;

            if ($cantidad > $anterior) {
                throw ValidationException::withMessages(['cantidad' => "Stock insuficiente para {$producto->nombre}. Disponible: {$anterior}."]);
            }

            $nuevo = $anterior - $cantidad;
            // Query builder (sin eventos): ver adicionarStock.
            Producto::query()->whereKey($producto->id)->decrement('stock_actual', $cantidad);

            return MovimientoInventario::create([
                'producto_id' => $producto->id,
                'user_id' => Auth::id(),
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'stock_anterior' => $anterior,
                'stock_nuevo' => $nuevo,
                'motivo' => $motivo ?? $tipo,
                'observaciones' => $observaciones,
            ]);
        });
    }

    /**
     * RF05: Cálculo automático Stock Total = Inicial + Entradas - Salidas
     */
    public function calcularStockTotal(Producto $producto): int
    {
        $inicial = MovimientoInventario::where('producto_id', $producto->id)
            ->where('tipo', MovimientoInventario::TIPO_INICIAL)
            ->orderByDesc('id')->value('cantidad') ?? $producto->stock_actual;

        $entradas = MovimientoInventario::where('producto_id', $producto->id)
            ->whereIn('tipo', MovimientoInventario::TIPOS_ENTRADA)
            ->where('tipo', '!=', MovimientoInventario::TIPO_INICIAL)
            ->sum('cantidad');

        $salidas = MovimientoInventario::where('producto_id', $producto->id)
            ->whereIn('tipo', MovimientoInventario::TIPOS_SALIDA)
            ->sum('cantidad');

        // Si no hay movimientos iniciales, usar stock actual como base
        if (! MovimientoInventario::where('producto_id', $producto->id)->where('tipo', MovimientoInventario::TIPO_INICIAL)->exists()) {
            return (int) $producto->stock_actual;
        }

        return (int) ($inicial + $entradas - $salidas);
    }

    /**
     * Historial trazable RF12
     */
    public function historial(Producto $producto, ?string $desde = null, ?string $hasta = null, ?string $tipo = null)
    {
        $q = MovimientoInventario::where('producto_id', $producto->id)->with(['user', 'producto'])->orderByDesc('created_at');

        if ($desde) {
            $q->whereDate('created_at', '>=', $desde);
        }
        if ($hasta) {
            $q->whereDate('created_at', '<=', $hasta);
        }
        if ($tipo) {
            $q->where('tipo', $tipo);
        }

        return $q->get();
    }

    /**
     * Valorización: cálculo RF08. Delega en ReporteService (fuente única)
     * y adapta el formato histórico de este servicio.
     */
    public function valorizacionInventario(?int $categoriaId = null): array
    {
        $valorizacion = app(ReporteService::class)->valorizacionInventario($categoriaId);
        $formato = fn ($valor): string => number_format((float) $valor, 2, '.', '');

        return [
            'total_costo' => $formato($valorizacion['total_costo']),
            'total_venta' => $formato($valorizacion['total_venta']),
            'ganancia_potencial' => $formato($valorizacion['ganancia_potencial']),
            'productos' => $valorizacion['count'],
            'count' => $valorizacion['count'],
        ];
    }
}
