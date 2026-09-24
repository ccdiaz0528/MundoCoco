<?php

namespace App\Services;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventarioService
{
    /**
     * RF02: Registrar inventario inicial (fija el stock base) - trazable.
     */
    public function registrarInicial(Producto $producto, int $cantidad, ?string $observaciones = null, CarbonInterface|string|null $fecha = null): MovimientoInventario
    {
        if ($cantidad < 0) {
            throw ValidationException::withMessages(['cantidad' => 'La cantidad inicial no puede ser negativa.']);
        }
        $fecha = $this->fechaTransaccion($fecha);

        return DB::transaction(function () use ($producto, $cantidad, $observaciones, $fecha) {
            $producto = Producto::query()->lockForUpdate()->findOrFail($producto->id);

            return $this->registrar($producto, MovimientoInventario::TIPO_INICIAL, $cantidad, $cantidad, 'Inventario inicial', $observaciones, $fecha);
        });
    }

    /**
     * RF03: Adiciones - compras, devoluciones, ajustes positivos.
     */
    public function adicionarStock(Producto $producto, int $cantidad, string $tipo, ?string $motivo = null, ?string $observaciones = null, CarbonInterface|string|null $fecha = null): MovimientoInventario
    {
        if (! in_array($tipo, [MovimientoInventario::TIPO_COMPRA, MovimientoInventario::TIPO_DEVOLUCION, MovimientoInventario::TIPO_AJUSTE_POSITIVO], true)) {
            throw ValidationException::withMessages(['tipo' => 'Tipo de entrada no válido. Use: compra, devolucion, ajuste_positivo.']);
        }

        if ($cantidad <= 0) {
            throw ValidationException::withMessages(['cantidad' => 'La cantidad debe ser positiva y mayor a cero.']); // RF03 regla
        }
        $fecha = $this->fechaTransaccion($fecha);

        return DB::transaction(function () use ($producto, $cantidad, $tipo, $motivo, $observaciones, $fecha) {
            $producto = Producto::query()->lockForUpdate()->findOrFail($producto->id);

            return $this->registrar($producto, $tipo, $cantidad, $producto->stock_actual + $cantidad, $motivo ?? $tipo, $observaciones, $fecha);
        });
    }

    /**
     * Ajuste negativo / merma.
     */
    public function retirarStock(Producto $producto, int $cantidad, string $tipo, ?string $motivo = null, ?string $observaciones = null, CarbonInterface|string|null $fecha = null): MovimientoInventario
    {
        if (! in_array($tipo, [MovimientoInventario::TIPO_AJUSTE_NEGATIVO, MovimientoInventario::TIPO_MERMA], true)) {
            throw ValidationException::withMessages(['tipo' => 'Tipo de salida no válido.']);
        }

        if ($cantidad <= 0) {
            throw ValidationException::withMessages(['cantidad' => 'La cantidad debe ser positiva.']);
        }
        $fecha = $this->fechaTransaccion($fecha);

        return DB::transaction(function () use ($producto, $cantidad, $tipo, $motivo, $observaciones, $fecha) {
            $producto = Producto::query()->lockForUpdate()->findOrFail($producto->id);

            if ($cantidad > $producto->stock_actual) {
                throw ValidationException::withMessages(['cantidad' => "Stock insuficiente para {$producto->nombre}. Disponible: {$producto->stock_actual}."]);
            }

            return $this->registrar($producto, $tipo, $cantidad, $producto->stock_actual - $cantidad, $motivo ?? $tipo, $observaciones, $fecha);
        });
    }

    /**
     * RF03 "Producto(s) seleccionado(s)": registra el mismo tipo de
     * movimiento para varios productos en una sola transacción (todo o nada).
     *
     * @param  array<int|string, array{producto_id: mixed, cantidad: mixed}>  $lineas
     * @return Collection<int, MovimientoInventario>
     */
    public function registrarLote(string $tipo, array $lineas, ?string $motivo = null, ?string $observaciones = null, CarbonInterface|string|null $fecha = null): Collection
    {
        if ($lineas === []) {
            throw ValidationException::withMessages(['lineas' => 'Agregue al menos un producto.']);
        }

        return DB::transaction(function () use ($tipo, $lineas, $motivo, $observaciones, $fecha): Collection {
            $ids = collect($lineas)->pluck('producto_id')->map(fn ($id) => (int) $id);
            if ($ids->duplicates()->isNotEmpty()) {
                throw ValidationException::withMessages(['lineas' => 'Cada producto debe aparecer una sola vez.']);
            }
            // Bloqueo en orden de id (mismo orden que VentaService) para evitar interbloqueos.
            $productos = Producto::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            return collect($lineas)->values()->map(function (array $linea) use ($tipo, $productos, $motivo, $observaciones, $fecha): MovimientoInventario {
                $producto = $productos->get((int) $linea['producto_id'])
                    ?? throw ValidationException::withMessages(['lineas' => 'Uno de los productos no existe.']);
                $cantidad = (int) $linea['cantidad'];

                return match ($tipo) {
                    MovimientoInventario::TIPO_INICIAL => $this->registrarInicial($producto, $cantidad, $observaciones, $fecha),
                    MovimientoInventario::TIPO_AJUSTE_NEGATIVO, MovimientoInventario::TIPO_MERMA => $this->retirarStock($producto, $cantidad, $tipo, $motivo, $observaciones, $fecha),
                    MovimientoInventario::TIPO_VENTA => throw ValidationException::withMessages(['tipo' => 'Las ventas se registran automáticamente al vender, no manualmente.']),
                    default => $this->adicionarStock($producto, $cantidad, $tipo, $motivo, $observaciones, $fecha),
                };
            });
        });
    }

    /**
     * RF05: Stock_Total = Stock_Inicial + Entradas - Salidas, calculado desde
     * los movimientos. Un "inicial" fija la base, por eso solo cuentan los
     * movimientos desde el último inicial (o desde 0 si nunca hubo uno).
     */
    public function calcularStockTotal(Producto $producto): int
    {
        return $this->stockCalculado([$producto->id])[$producto->id] ?? 0;
    }

    /**
     * RF05 en lote (una consulta): producto_id => stock calculado.
     *
     * @param  array<int, int>|null  $productoIds  null = todos
     * @return array<int, int>
     */
    public function stockCalculado(?array $productoIds = null): array
    {
        $tabla = (new MovimientoInventario)->getTable();
        $ultimoInicial = MovimientoInventario::query()
            ->where('tipo', MovimientoInventario::TIPO_INICIAL)
            ->groupBy('producto_id')
            ->selectRaw('producto_id, MAX(id) as ultimo_id');

        $entradas = "'".implode("','", MovimientoInventario::TIPOS_ENTRADA)."'";

        return MovimientoInventario::query()
            ->leftJoinSub($ultimoInicial, 'base', 'base.producto_id', '=', "{$tabla}.producto_id")
            ->where(fn ($q) => $q->whereNull('base.ultimo_id')->orWhereColumn("{$tabla}.id", '>=', 'base.ultimo_id'))
            ->when($productoIds !== null, fn ($q) => $q->whereIn("{$tabla}.producto_id", $productoIds))
            ->groupBy("{$tabla}.producto_id")
            ->selectRaw("{$tabla}.producto_id as producto_id")
            ->selectRaw("SUM(CASE WHEN {$tabla}.tipo IN ({$entradas}) THEN {$tabla}.cantidad ELSE -{$tabla}.cantidad END) as calculado")
            ->toBase()
            ->get()
            ->mapWithKeys(fn ($fila) => [(int) $fila->producto_id => (int) $fila->calculado])
            ->all();
    }

    /**
     * OE4 "precisión de inventario": productos cuyo stock registrado no
     * coincide con el calculado por RF05.
     *
     * @return Collection<int, array{producto: Producto, registrado: int, calculado: int}>
     */
    public function inconsistenciasStock(): Collection
    {
        $calculado = $this->stockCalculado();

        return Producto::query()->with('categoria')->orderBy('nombre')->get()
            ->map(fn (Producto $producto) => [
                'producto' => $producto,
                'registrado' => (int) $producto->stock_actual,
                'calculado' => $calculado[$producto->id] ?? 0,
            ])
            ->filter(fn (array $fila) => $fila['registrado'] !== $fila['calculado'])
            ->values();
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

        return $valorizacion + ['productos' => $valorizacion['count']];
    }

    /**
     * Aplica el nuevo stock (query builder, sin eventos: el ProductoObserver
     * solo traza ajustes manuales), crea el movimiento RF12 y audita (RNF04).
     */
    private function registrar(Producto $producto, string $tipo, int $cantidad, int $nuevo, string $motivo, ?string $observaciones, Carbon $fecha): MovimientoInventario
    {
        $anterior = (int) $producto->stock_actual;
        Producto::query()->whereKey($producto->id)->update(['stock_actual' => $nuevo]);
        $producto->stock_actual = $nuevo;

        $movimiento = MovimientoInventario::create([
            'producto_id' => $producto->id,
            'user_id' => Auth::id(),
            'tipo' => $tipo,
            'fecha_movimiento' => $fecha,
            'cantidad' => $cantidad,
            'stock_anterior' => $anterior,
            'stock_nuevo' => $nuevo,
            'motivo' => $motivo,
            'observaciones' => $observaciones,
        ]);

        AuditService::logStockAjuste($producto, $anterior, $nuevo, $tipo);

        return $movimiento;
    }

    /** RF02/RF03: fecha indicada por el usuario (por defecto ahora), nunca futura. */
    private function fechaTransaccion(CarbonInterface|string|null $fecha): Carbon
    {
        $fecha = $fecha === null || $fecha === '' ? now() : Carbon::parse($fecha);
        if ($fecha->isFuture()) {
            throw ValidationException::withMessages(['fecha_movimiento' => 'La fecha del movimiento no puede ser futura.']);
        }

        return $fecha;
    }
}
