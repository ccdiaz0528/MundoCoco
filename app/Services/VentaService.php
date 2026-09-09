<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\MetodoPago;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VentaService
{
    /**
     * Registra una venta usando el precio vigente del producto y descuenta el
     * inventario dentro de la misma transacción.
     *
     * @param  array<string, mixed>  $atributos
     * @param  array<int, array<string, mixed>>  $detalles
     */
    public function crear(array $atributos, array $detalles): Venta
    {
        return DB::transaction(function () use ($atributos, $detalles): Venta {
            $fechaVenta = Carbon::parse($atributos['fecha_venta'] ?? now());
            $this->asegurarCajaNoCerrada($fechaVenta);
            $metodoPago = $this->obtenerMetodoPagoActivo($atributos['metodo_pago_id'] ?? null);
            [$productos, $cantidades] = $this->bloquearYValidarProductos($detalles);

            $totalCentavos = 0;
            $lineas = [];

            foreach ($detalles as $detalle) {
                $producto = $productos->get((int) $detalle['producto_id']);
                $cantidad = (int) $detalle['cantidad'];
                $precioCentavos = $this->aCentavos($producto->precio_venta);
                $subtotalCentavos = $precioCentavos * $cantidad;
                $totalCentavos += $subtotalCentavos;

                $lineas[] = [
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $this->desdeCentavos($precioCentavos),
                    'subtotal' => $this->desdeCentavos($subtotalCentavos),
                ];
            }

            $venta = Venta::query()->create([
                'metodo_pago_id' => $metodoPago->id,
                'total' => $this->desdeCentavos($totalCentavos),
                'observaciones' => $atributos['observaciones'] ?? null,
                'fecha_venta' => $fechaVenta,
            ]);

            $venta->detalles()->createMany($lineas);

            foreach ($cantidades as $productoId => $cantidad) {
                $producto = $productos->get($productoId);
                $stockAnterior = $producto->stock_actual;
                $producto->decrement('stock_actual', $cantidad);
                $stockNuevo = $stockAnterior - $cantidad;

                MovimientoInventario::create([
                    'producto_id' => $productoId,
                    'user_id' => Auth::id(),
                    'tipo' => MovimientoInventario::TIPO_VENTA,
                    'cantidad' => $cantidad,
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $stockNuevo,
                    'motivo' => 'Venta #'.$venta->id,
                    'observaciones' => $venta->observaciones,
                    'referencia_type' => Venta::class,
                    'referencia_id' => $venta->id,
                ]);
            }

            AuditService::logVentaCreada($venta);

            return $venta->load(['detalles.producto', 'metodoPago']);
        }, 3);
    }

    public function asegurarFechaVentaOperable(Carbon|string $fechaVenta): void
    {
        $this->asegurarCajaNoCerrada(Carbon::parse($fechaVenta));
    }

    /**
     * Comprueba stock con bloqueos de fila y devuelve los importes confiables
     * que deben persistirse para los detalles enviados por Filament.
     *
     * @param  array<int, array<string, mixed>>  $detalles
     * @return array{detalles: array<int, array{producto_id: int, cantidad: int, precio_unitario: string, subtotal: string}>, cantidades: array<int, int>, total: string}
     */
    public function prepararDetalles(array $detalles): array
    {
        [$productos, $cantidades] = $this->bloquearYValidarProductos($detalles);
        $lineas = [];
        $totalCentavos = 0;

        foreach ($detalles as $detalle) {
            $producto = $productos->get((int) $detalle['producto_id']);
            $cantidad = (int) $detalle['cantidad'];
            $precioCentavos = $this->aCentavos($producto->precio_venta);
            $subtotalCentavos = $precioCentavos * $cantidad;
            $totalCentavos += $subtotalCentavos;

            $lineas[] = [
                'producto_id' => $producto->id,
                'cantidad' => $cantidad,
                'precio_unitario' => $this->desdeCentavos($precioCentavos),
                'subtotal' => $this->desdeCentavos($subtotalCentavos),
            ];
        }

        return [
            'detalles' => $lineas,
            'cantidades' => $cantidades,
            'total' => $this->desdeCentavos($totalCentavos),
        ];
    }

    /** @param array<int, int> $cantidades */
    public function descontarInventario(array $cantidades): void
    {
        foreach ($cantidades as $productoId => $cantidad) {
            $producto = Producto::query()->whereKey($productoId)->first();
            if (! $producto) {
                continue;
            }
            $anterior = $producto->stock_actual;
            Producto::query()->whereKey($productoId)->decrement('stock_actual', $cantidad);
            MovimientoInventario::create([
                'producto_id' => $productoId,
                'user_id' => Auth::id(),
                'tipo' => MovimientoInventario::TIPO_VENTA,
                'cantidad' => $cantidad,
                'stock_anterior' => $anterior,
                'stock_nuevo' => $anterior - $cantidad,
                'motivo' => 'Venta vía Filament',
            ]);
        }
    }

    /**
     * RF04: productos que quedaron con stock bajo el mínimo tras la venta.
     * Se usa para alertar al vendedor inmediatamente después de registrarla.
     *
     * @param  array<int, int>  $cantidades  mapa producto_id => cantidad vendida
     * @return Collection<int, Producto>
     */
    public function productosBajoMinimo(array $cantidades): Collection
    {
        if ($cantidades === []) {
            return collect();
        }

        return Producto::query()
            ->whereIn('id', array_keys($cantidades))
            ->get()
            ->filter(fn (Producto $producto) => $producto->stockBajo())
            ->values();
    }

    /**
     * Recalcula una edición después de que Filament guardó sus relaciones.
     * El método asume una transacción exterior (la que provee Filament).
     *
     * @param  array<int, array<string, mixed>>  $detallesAnteriores
     */
    public function reconciliarEdicion(Venta $venta, array $detallesAnteriores, Carbon|string $fechaVentaAnterior): void
    {
        DB::transaction(function () use ($venta, $detallesAnteriores, $fechaVentaAnterior): void {
            $this->asegurarCajaNoCerrada(Carbon::parse($fechaVentaAnterior));
            $this->asegurarCajaNoCerrada(Carbon::parse($venta->fecha_venta));
            $this->obtenerMetodoPagoActivo($venta->metodo_pago_id);
            $cantidadesAnteriores = $this->agruparCantidades($detallesAnteriores);
            $detallesNuevos = $venta->detalles()->get();
            if ($detallesNuevos->isEmpty()) {
                throw ValidationException::withMessages(['detalles' => 'La venta debe tener al menos un producto.']);
            }
            $cantidadesNuevas = $this->agruparCantidades($detallesNuevos->map(fn ($detalle) => $detalle->only(['producto_id', 'cantidad']))->all());
            $ids = array_unique(array_merge(array_keys($cantidadesAnteriores), array_keys($cantidadesNuevas)));
            $productos = Producto::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($cantidadesNuevas as $productoId => $cantidadNueva) {
                $producto = $productos->get($productoId);
                $disponible = ($producto?->stock_actual ?? 0) + ($cantidadesAnteriores[$productoId] ?? 0);
                if ($producto === null || ! $producto->activo || $cantidadNueva > $disponible) {
                    throw ValidationException::withMessages(['detalles' => 'La edición supera el stock disponible o contiene un producto inactivo.']);
                }
            }

            foreach ($cantidadesAnteriores as $productoId => $cantidad) {
                $productos->get($productoId)->increment('stock_actual', $cantidad);
            }

            $totalCentavos = 0;
            foreach ($detallesNuevos as $detalle) {
                $precioCentavos = $this->aCentavos($productos->get($detalle->producto_id)->precio_venta);
                $subtotalCentavos = $precioCentavos * $detalle->cantidad;
                $detalle->updateQuietly([
                    'precio_unitario' => $this->desdeCentavos($precioCentavos),
                    'subtotal' => $this->desdeCentavos($subtotalCentavos),
                ]);
                $totalCentavos += $subtotalCentavos;
            }

            foreach ($cantidadesNuevas as $productoId => $cantidad) {
                $productos->get($productoId)->decrement('stock_actual', $cantidad);
            }

            $venta->updateQuietly(['total' => $this->desdeCentavos($totalCentavos)]);
            app(CajaService::class)->recalcularCajaAbierta($venta->fecha_venta);
        }, 3);
    }

    /**
     * @param  array<int, array<string, mixed>>  $detalles
     * @return array{0: Collection<int, Producto>, 1: array<int, int>}
     */
    private function bloquearYValidarProductos(array $detalles): array
    {
        if ($detalles === []) {
            throw ValidationException::withMessages(['detalles' => 'La venta debe tener al menos un producto.']);
        }

        $cantidades = $this->agruparCantidades($detalles);
        foreach ($detalles as $indice => $detalle) {
            $productoId = $detalle['producto_id'] ?? null;
            $cantidad = $detalle['cantidad'] ?? null;

            if (! filter_var($productoId, FILTER_VALIDATE_INT) || ! is_string($cantidad) && ! is_int($cantidad) || ! preg_match('/^[1-9][0-9]*$/', (string) $cantidad)) {
                throw ValidationException::withMessages(["detalles.{$indice}" => 'Cada detalle debe indicar un producto y una cantidad entera positiva.']);
            }
        }

        $productos = Producto::query()
            ->whereIn('id', array_keys($cantidades))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($cantidades as $productoId => $cantidad) {
            $producto = $productos->get($productoId);

            if ($producto === null || ! $producto->activo) {
                throw ValidationException::withMessages(['detalles' => 'Uno de los productos no existe o está inactivo.']);
            }

            if ($cantidad > $producto->stock_actual) {
                throw ValidationException::withMessages(['detalles' => "Stock insuficiente para {$producto->nombre}."]);
            }
        }

        return [$productos, $cantidades];
    }

    /** @param array<int, array<string, mixed>> $detalles @return array<int, int> */
    private function agruparCantidades(array $detalles): array
    {
        $cantidades = [];
        foreach ($detalles as $detalle) {
            $productoId = $detalle['producto_id'] ?? null;
            $cantidad = $detalle['cantidad'] ?? null;
            if (filter_var($productoId, FILTER_VALIDATE_INT) && preg_match('/^[1-9][0-9]*$/', (string) $cantidad)) {
                $cantidades[(int) $productoId] = ($cantidades[(int) $productoId] ?? 0) + (int) $cantidad;
            }
        }

        return $cantidades;
    }

    private function obtenerMetodoPagoActivo(mixed $metodoPagoId): MetodoPago
    {
        $metodoPago = MetodoPago::query()
            ->whereKey($metodoPagoId)
            ->where('activo', true)
            ->first();

        if ($metodoPago === null) {
            throw ValidationException::withMessages(['metodo_pago_id' => 'Seleccione un método de pago activo.']);
        }

        return $metodoPago;
    }

    private function asegurarCajaNoCerrada(Carbon $fechaVenta): void
    {
        $caja = Caja::query()->whereDate('fecha', $fechaVenta)->lockForUpdate()->first();

        if ($caja?->estado === 'cerrada') {
            throw ValidationException::withMessages(['fecha_venta' => 'No se pueden registrar ventas en una caja cerrada.']);
        }
    }

    private function aCentavos(mixed $valor): int
    {
        $normalizado = str_replace(',', '.', trim((string) $valor));
        if (! preg_match('/^([0-9]+)(?:\.([0-9]{1,2}))?$/', $normalizado, $coincidencias)) {
            throw ValidationException::withMessages(['total' => 'El valor monetario no es válido.']);
        }

        return ((int) $coincidencias[1] * 100) + (int) str_pad($coincidencias[2] ?? '', 2, '0');
    }

    private function desdeCentavos(int $centavos): string
    {
        return sprintf('%d.%02d', intdiv($centavos, 100), abs($centavos % 100));
    }
}
