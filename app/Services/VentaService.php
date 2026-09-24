<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\MetodoPago;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Venta;
use App\Support\Dinero;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Único punto de entrada para registrar y editar ventas. El panel Filament
 * delega aquí (CreateVenta/EditVenta), de modo que la lógica probada en los
 * tests es exactamente la que corre en producción.
 */
class VentaService
{
    use Dinero;

    /**
     * Registra una venta usando el precio vigente del producto y descuenta el
     * inventario dentro de la misma transacción.
     *
     * @param  array<string, mixed>  $atributos
     * @param  array<int|string, array<string, mixed>>  $detalles
     */
    public function crear(array $atributos, array $detalles): Venta
    {
        return DB::transaction(function () use ($atributos, $detalles): Venta {
            $fechaVenta = Carbon::parse($atributos['fecha_venta'] ?? now());
            $this->asegurarFechaNoFutura($fechaVenta);
            $this->asegurarCajaNoCerrada($fechaVenta);
            $metodoPago = $this->obtenerMetodoPagoActivo($atributos['metodo_pago_id'] ?? null);
            [$productos, $cantidades] = $this->bloquearYValidarProductos($detalles);
            [$lineas, $totalCentavos] = $this->calcularLineas($detalles, $productos);

            $venta = Venta::query()->create([
                'metodo_pago_id' => $metodoPago->id,
                'total' => $this->desdeCentavos($totalCentavos),
                'observaciones' => $atributos['observaciones'] ?? null,
                'fecha_venta' => $fechaVenta,
            ]);

            $venta->detalles()->createMany($lineas);

            foreach ($cantidades as $productoId => $cantidad) {
                $this->moverStock($productos->get($productoId), -$cantidad, $venta, 'Venta #'.$venta->id, $fechaVenta);
            }

            AuditService::logVentaCreada($venta);
            $this->auditarPreciosModificados($venta, $lineas);

            return $venta->load(['detalles.producto', 'metodoPago']);
        }, 3);
    }

    /**
     * Edita una venta de forma atómica: valida el nuevo stock contando lo que
     * la venta ya tenía reservado, reemplaza los detalles con precios del
     * servidor y traza la diferencia neta por producto (RF12): salida si se
     * vendió más, devolución si se vendió menos.
     *
     * @param  array<string, mixed>  $atributos
     * @param  array<int|string, array<string, mixed>>  $detalles
     */
    public function actualizar(Venta $venta, array $atributos, array $detalles): Venta
    {
        return DB::transaction(function () use ($venta, $atributos, $detalles): Venta {
            $venta = Venta::query()->with('detalles')->lockForUpdate()->findOrFail($venta->id);
            $this->asegurarNoAnulada($venta);
            $fechaAnterior = Carbon::parse($venta->fecha_venta);
            $fechaNueva = Carbon::parse($atributos['fecha_venta'] ?? $venta->fecha_venta);
            $this->asegurarFechaNoFutura($fechaNueva);

            // Bloqueo en orden cronológico para no cruzar el orden entre transacciones.
            $fechas = collect([$fechaAnterior, $fechaNueva])
                ->sort()
                ->unique(fn (Carbon $fecha) => $fecha->toDateString());
            foreach ($fechas as $fecha) {
                $this->asegurarCajaNoCerrada($fecha);
            }

            $metodoPago = $this->obtenerMetodoPagoActivo($atributos['metodo_pago_id'] ?? $venta->metodo_pago_id);
            $anteriores = $this->agruparCantidades($venta->detalles->map->only(['producto_id', 'cantidad'])->all());
            $valoresAnteriores = $venta->only(['metodo_pago_id', 'total', 'observaciones', 'fecha_venta'])
                + ['detalles' => $venta->detalles->map->only(['producto_id', 'cantidad', 'precio_unitario', 'subtotal'])->all()];

            [$productos, $nuevas] = $this->bloquearYValidarProductos($detalles, $anteriores);
            [$lineas, $totalCentavos] = $this->calcularLineas($detalles, $productos);

            $venta->detalles()->delete();
            $venta->detalles()->createMany($lineas);
            // Sin eventos: la caja se recalcula explícitamente abajo.
            $venta->fill([
                'metodo_pago_id' => $metodoPago->id,
                'total' => $this->desdeCentavos($totalCentavos),
                'observaciones' => array_key_exists('observaciones', $atributos) ? $atributos['observaciones'] : $venta->observaciones,
                'fecha_venta' => $fechaNueva,
            ])->saveQuietly();

            foreach ($productos as $productoId => $producto) {
                $neto = ($anteriores[$productoId] ?? 0) - ($nuevas[$productoId] ?? 0);
                if ($neto !== 0) {
                    $this->moverStock($producto, $neto, $venta, 'Edición venta #'.$venta->id);
                }
            }

            $cajaService = app(CajaService::class);
            $cajaService->recalcularCajaAbierta($fechaAnterior);
            if (! $fechaNueva->isSameDay($fechaAnterior)) {
                $cajaService->recalcularCajaAbierta($fechaNueva);
            }

            $venta->load(['detalles.producto', 'metodoPago']);
            AuditService::logVentaEditada($venta, $valoresAnteriores);
            $this->auditarPreciosModificados($venta, $lineas);

            return $venta;
        }, 3);
    }

    /**
     * Anula una venta (devolución total): devuelve el stock de cada línea como
     * movimiento "devolucion" (RF12), la excluye de caja y reportes y la
     * audita. La venta no se borra. Solo con la caja de su fecha abierta.
     */
    public function anular(Venta $venta, string $motivo): Venta
    {
        $motivo = trim($motivo);
        if ($motivo === '') {
            throw ValidationException::withMessages(['motivo_anulacion' => 'Indique el motivo de la anulación.']);
        }

        return DB::transaction(function () use ($venta, $motivo): Venta {
            $venta = Venta::query()->with('detalles')->lockForUpdate()->findOrFail($venta->id);
            $this->asegurarNoAnulada($venta);
            $this->asegurarCajaNoCerrada(Carbon::parse($venta->fecha_venta));

            $cantidades = $this->agruparCantidades($venta->detalles->map->only(['producto_id', 'cantidad'])->all());
            $productos = Producto::withTrashed()
                ->whereIn('id', array_keys($cantidades))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $venta->forceFill([
                'anulada_at' => now(),
                'anulada_por' => Auth::id(),
                'motivo_anulacion' => $motivo,
            ])->saveQuietly();

            foreach ($cantidades as $productoId => $cantidad) {
                $this->moverStock($productos->get($productoId), $cantidad, $venta, 'Anulación venta #'.$venta->id.': '.$motivo);
            }

            app(CajaService::class)->recalcularCajaAbierta($venta->fecha_venta);
            AuditService::log('venta_anulada', $venta, ['motivo' => $motivo, 'total' => (string) $venta->total]);

            return $venta->load(['detalles.producto', 'metodoPago']);
        }, 3);
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
     * Bloquea las filas de producto (en orden de id) y valida existencia,
     * estado y stock. $reservadas son las cantidades que la venta ya tenía
     * (edición): cuentan como disponibles y permiten conservar un producto
     * que fue desactivado después de venderlo.
     *
     * @param  array<int|string, array<string, mixed>>  $detalles
     * @param  array<int, int>  $reservadas
     * @return array{0: Collection<int, Producto>, 1: array<int, int>}
     */
    private function bloquearYValidarProductos(array $detalles, array $reservadas = []): array
    {
        if ($detalles === []) {
            throw ValidationException::withMessages(['detalles' => 'La venta debe tener al menos un producto.']);
        }

        foreach ($detalles as $indice => $detalle) {
            if (! filter_var($detalle['producto_id'] ?? null, FILTER_VALIDATE_INT) || $this->normalizarCantidad($detalle['cantidad'] ?? null) === null) {
                throw ValidationException::withMessages(["detalles.{$indice}" => 'Cada detalle debe indicar un producto y una cantidad entera positiva.']);
            }
        }

        $cantidades = $this->agruparCantidades($detalles);
        // withTrashed: una edición puede conservar un producto eliminado después de venderlo.
        $productos = Producto::withTrashed()
            ->whereIn('id', array_unique(array_merge(array_keys($cantidades), array_keys($reservadas))))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($cantidades as $productoId => $cantidad) {
            $producto = $productos->get($productoId);
            $reservada = $reservadas[$productoId] ?? 0;
            $vendible = $producto !== null && $producto->activo && ! $producto->trashed();

            if ($producto === null || (! $vendible && $reservada === 0)) {
                throw ValidationException::withMessages(['detalles' => 'Uno de los productos no existe o está inactivo.']);
            }

            if ($cantidad > $producto->stock_actual + $reservada) {
                throw ValidationException::withMessages(['detalles' => "Stock insuficiente para {$producto->nombre}."]);
            }
        }

        return [$productos, $cantidades];
    }

    /**
     * Calcula las líneas. El precio base sale siempre de la BD; el precio
     * aplicado puede diferir (RF04) pero lo valida el servidor. Subtotal y
     * total nunca se toman del navegador.
     *
     * @param  array<int|string, array<string, mixed>>  $detalles
     * @param  Collection<int, Producto>  $productos
     * @return array{0: array<int, array{producto_id: int, cantidad: int, precio_unitario: string, precio_base: string, subtotal: string}>, 1: int}
     */
    private function calcularLineas(array $detalles, Collection $productos): array
    {
        $lineas = [];
        $totalCentavos = 0;

        foreach ($detalles as $indice => $detalle) {
            $producto = $productos->get((int) $detalle['producto_id']);
            $cantidad = $this->normalizarCantidad($detalle['cantidad']);
            $baseCentavos = $this->aCentavos($producto->precio_venta);
            $precioCentavos = $this->precioAplicado($detalle['precio_unitario'] ?? null, $baseCentavos, "detalles.{$indice}.precio_unitario");
            $subtotalCentavos = $precioCentavos * $cantidad;
            $totalCentavos += $subtotalCentavos;

            $lineas[] = [
                'producto_id' => $producto->id,
                'cantidad' => $cantidad,
                'precio_unitario' => $this->desdeCentavos($precioCentavos),
                'precio_base' => $this->desdeCentavos($baseCentavos),
                'subtotal' => $this->desdeCentavos($subtotalCentavos),
            ];
        }

        return [$lineas, $totalCentavos];
    }

    /**
     * RF04: sin precio propuesto (o igual al base) se usa el precio base. Un
     * precio distinto debe respetar el rango de RF01 configurado en
     * config/mundococo.php; así un descuento o recargo nunca queda libre.
     */
    private function precioAplicado(mixed $propuesto, int $baseCentavos, string $campo): int
    {
        if ($propuesto === null || $propuesto === '') {
            return $baseCentavos;
        }

        $precioCentavos = $this->aCentavos(is_float($propuesto) ? number_format($propuesto, 2, '.', '') : $propuesto, $campo);
        if ($precioCentavos === $baseCentavos) {
            return $baseCentavos;
        }

        $minimo = (int) config('mundococo.precio_venta_min') * 100;
        $maximo = (int) config('mundococo.precio_venta_max') * 100;
        if ($precioCentavos < $minimo || $precioCentavos > $maximo) {
            throw ValidationException::withMessages([$campo => 'El precio de venta debe estar entre $'.number_format($minimo / 100, 0, ',', '.').' y $'.number_format($maximo / 100, 0, ',', '.').'.']);
        }

        return $precioCentavos;
    }

    /**
     * RNF04: deja rastro de las líneas vendidas a un precio distinto del base.
     *
     * @param  array<int, array{producto_id: int, precio_unitario: string, precio_base: string}>  $lineas
     */
    private function auditarPreciosModificados(Venta $venta, array $lineas): void
    {
        $modificadas = array_values(array_filter($lineas, fn (array $linea): bool => $linea['precio_unitario'] !== $linea['precio_base']));
        if ($modificadas === []) {
            return;
        }

        AuditService::log('venta_precio_modificado', $venta, [
            'lineas' => array_map(fn (array $linea): array => [
                'producto_id' => $linea['producto_id'],
                'precio_base' => $linea['precio_base'],
                'precio_unitario' => $linea['precio_unitario'],
            ], $modificadas),
        ]);
    }

    private function asegurarNoAnulada(Venta $venta): void
    {
        if ($venta->anulada()) {
            throw ValidationException::withMessages(['venta' => 'La venta está anulada y no admite cambios.']);
        }
    }

    /** RF04: la fecha y hora de venta la indica el usuario, pero no puede ser futura. */
    private function asegurarFechaNoFutura(Carbon $fechaVenta): void
    {
        if ($fechaVenta->isFuture()) {
            throw ValidationException::withMessages(['fecha_venta' => 'La fecha de venta no puede ser futura.']);
        }
    }

    /**
     * Aplica un cambio de stock ya validado (negativo = salida por venta,
     * positivo = devolución por edición) y lo traza en MovimientoInventario.
     * Query builder sin eventos: el ProductoObserver solo traza ajustes manuales.
     */
    private function moverStock(Producto $producto, int $delta, Venta $venta, string $motivo, ?Carbon $fecha = null): void
    {
        $stockAnterior = $producto->stock_actual;
        Producto::query()->whereKey($producto->id)->increment('stock_actual', $delta);
        $producto->stock_actual = $stockAnterior + $delta;

        MovimientoInventario::create([
            'producto_id' => $producto->id,
            'user_id' => Auth::id(),
            'tipo' => $delta < 0 ? MovimientoInventario::TIPO_VENTA : MovimientoInventario::TIPO_DEVOLUCION,
            'fecha_movimiento' => $fecha ?? now(),
            'cantidad' => abs($delta),
            'stock_anterior' => $stockAnterior,
            'stock_nuevo' => $stockAnterior + $delta,
            'motivo' => $motivo,
            'observaciones' => $venta->observaciones,
            'referencia_type' => Venta::class,
            'referencia_id' => $venta->id,
        ]);
    }

    /** @param array<int|string, array<string, mixed>> $detalles @return array<int, int> */
    private function agruparCantidades(array $detalles): array
    {
        $cantidades = [];
        foreach ($detalles as $detalle) {
            $productoId = $detalle['producto_id'] ?? null;
            $cantidad = $this->normalizarCantidad($detalle['cantidad'] ?? null);
            if (filter_var($productoId, FILTER_VALIDATE_INT) && $cantidad !== null) {
                $cantidades[(int) $productoId] = ($cantidades[(int) $productoId] ?? 0) + $cantidad;
            }
        }

        return $cantidades;
    }

    /**
     * Cantidad entera positiva o null. Acepta int, string de dígitos o float
     * entero (el TextInput numérico de Filament hidrata 4 como 4.0).
     */
    private function normalizarCantidad(mixed $cantidad): ?int
    {
        if (is_float($cantidad) && floor($cantidad) === $cantidad && $cantidad >= 1 && $cantidad <= PHP_INT_MAX) {
            return (int) $cantidad;
        }

        if ((is_int($cantidad) || is_string($cantidad)) && preg_match('/^[1-9][0-9]*$/', (string) $cantidad)) {
            return (int) $cantidad;
        }

        return null;
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
}
