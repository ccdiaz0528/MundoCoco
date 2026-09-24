<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\Gasto;
use App\Models\MetodoPago;
use App\Models\Venta;
use App\Support\Dinero;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CajaService
{
    use Dinero;

    /** @param array<string, mixed> $atributos */
    public function abrir(array $atributos): Caja
    {
        return DB::transaction(function () use ($atributos): Caja {
            $fecha = Carbon::parse($atributos['fecha'] ?? now())->startOfDay();

            if (Caja::query()->whereDate('fecha', $fecha)->where('estado', 'abierta')->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['fecha' => 'Ya existe una caja abierta para esta fecha.']);
            }

            $totales = $this->totalesPorFecha($fecha);
            $saldoInicial = $this->aCentavos($atributos['saldo_inicial'] ?? 0, 'saldo_inicial');
            $saldoTeorico = $saldoInicial + $this->aCentavos($totales['total']) - $this->aCentavos($totales['gastos']);

            return Caja::query()->create([
                'fecha' => $fecha->toDateString(),
                'estado' => 'abierta',
                'saldo_inicial' => $this->desdeCentavos($saldoInicial),
                ...$this->camposTotales($totales),
                'saldo_teorico' => $this->desdeCentavos($saldoTeorico),
                'saldo_real' => $this->desdeCentavos($saldoInicial),
                'diferencia' => null,
                'observaciones' => $atributos['observaciones'] ?? null,
            ]);
        }, 3);
    }

    /** @param array<string, mixed> $datos */
    public function cerrar(Caja $caja, array $datos): Caja
    {
        return DB::transaction(function () use ($caja, $datos): Caja {
            $caja = Caja::query()->lockForUpdate()->findOrFail($caja->id);

            if ($caja->estado !== 'abierta') {
                throw ValidationException::withMessages(['caja' => 'La caja ya fue cerrada.']);
            }

            $totales = $this->totalesPorFecha($caja->fecha);
            $saldoReal = $this->aCentavos($datos['saldo_real'] ?? null, 'saldo_real');
            // RF11: Saldo Teórico = Base + Ventas - Gastos
            $esperado = $this->aCentavos($caja->saldo_inicial) + $this->aCentavos($totales['total']) - $this->aCentavos($totales['gastos']);

            $caja->fill([
                ...$this->camposTotales($totales),
                'saldo_teorico' => $this->desdeCentavos($esperado),
                'saldo_real' => $this->desdeCentavos($saldoReal),
                'diferencia' => $this->desdeCentavos($saldoReal - $esperado),
                'estado' => 'cerrada',
                'fecha_cierre' => now(),
                'observaciones_cierre' => $datos['observaciones_cierre'] ?? null,
            ])->saveQuietly();

            AuditService::logCajaCerrada($caja);

            return $caja;
        }, 3);
    }

    public function recalcularCajaAbierta(CarbonInterface|string $fecha): void
    {
        DB::transaction(function () use ($fecha): void {
            $fecha = Carbon::parse($fecha);
            $caja = Caja::query()
                ->whereDate('fecha', $fecha)
                ->where('estado', 'abierta')
                ->lockForUpdate()
                ->first();

            if ($caja === null) {
                return;
            }

            $totales = $this->totalesPorFecha($fecha);
            $saldoTeorico = $this->aCentavos($caja->saldo_inicial) + $this->aCentavos($totales['total']) - $this->aCentavos($totales['gastos']);
            $caja->fill([
                ...$this->camposTotales($totales),
                'saldo_teorico' => $this->desdeCentavos($saldoTeorico),
            ])->saveQuietly();
        }, 3);
    }

    /** @return array{efectivo: string, nequi: string, transferencias: string, tarjetas: string, total: string, gastos: string} */
    public function totalesPorFecha(CarbonInterface|string $fecha): array
    {
        $fecha = Carbon::parse($fecha);
        $metodos = MetodoPago::query()
            ->whereIn('nombre', MetodoPago::NOMBRES)
            ->pluck('id', 'nombre');

        $totalPorMetodo = Venta::vigentes()
            ->whereDate('fecha_venta', $fecha)
            ->selectRaw('metodo_pago_id, SUM(total) as total')
            ->groupBy('metodo_pago_id')
            ->pluck('total', 'metodo_pago_id');

        $efectivo = $this->aCentavos($totalPorMetodo->get($metodos->get(MetodoPago::EFECTIVO), 0));
        $nequi = $this->aCentavos($totalPorMetodo->get($metodos->get(MetodoPago::NEQUI), 0));
        $transferencias = $this->aCentavos($totalPorMetodo->get($metodos->get(MetodoPago::TRANSFERENCIA), 0));
        $tarjetas = $this->aCentavos($totalPorMetodo->get($metodos->get(MetodoPago::TARJETA), 0));
        $total = $this->aCentavos(Venta::vigentes()->whereDate('fecha_venta', $fecha)->sum('total'));
        // RF11: gastos del día (compras/materia prima)
        $gastos = $this->aCentavos(Gasto::whereDate('fecha', $fecha)->sum('monto'));

        return [
            'efectivo' => $this->desdeCentavos($efectivo),
            'nequi' => $this->desdeCentavos($nequi),
            'transferencias' => $this->desdeCentavos($transferencias),
            'tarjetas' => $this->desdeCentavos($tarjetas),
            'total' => $this->desdeCentavos($total),
            'gastos' => $this->desdeCentavos($gastos),
        ];
    }

    /**
     * Columnas total_* de la caja a partir de totalesPorFecha().
     *
     * @param  array{efectivo: string, nequi: string, transferencias: string, tarjetas: string, total: string, gastos: string}  $totales
     * @return array<string, string>
     */
    private function camposTotales(array $totales): array
    {
        return [
            'total_efectivo' => $totales['efectivo'],
            'total_nequi' => $totales['nequi'],
            'total_transferencias' => $totales['transferencias'],
            'total_tarjetas' => $totales['tarjetas'],
            'total_ventas' => $totales['total'],
            'total_gastos' => $totales['gastos'],
        ];
    }
}
