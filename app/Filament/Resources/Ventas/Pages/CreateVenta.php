<?php

namespace App\Filament\Resources\Ventas\Pages;

use App\Filament\Resources\Ventas\VentaResource;
use App\Services\CajaService;
use App\Services\VentaService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateVenta extends CreateRecord
{
    protected static string $resource = VentaResource::class;

    /** @var array<int, array{producto_id: int, cantidad: int, precio_unitario: string, subtotal: string}> */
    private array $detallesProcesados = [];

    /** @var array<int, int> */
    private array $cantidadesProcesadas = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $ventaService = app(VentaService::class);
        $ventaService->asegurarFechaVentaOperable($data['fecha_venta'] ?? now());
        $preparada = $ventaService->prepararDetalles($this->data['detalles'] ?? []);
        $this->detallesProcesados = $preparada['detalles'];
        $this->cantidadesProcesadas = $preparada['cantidades'];

        // El total enviado por el navegador nunca es una fuente de verdad.
        $data['total'] = $preparada['total'];

        return $data;
    }

    protected function afterCreate(): void
    {
        $detallesPorProducto = collect($this->detallesProcesados)
            ->groupBy('producto_id')
            ->map(fn ($detalles) => $detalles->values());

        foreach ($this->record->detalles as $detalle) {
            $detallesDelProducto = $detallesPorProducto->get($detalle->producto_id);
            $detalleProcesado = $detallesDelProducto->shift();
            $detalle->updateQuietly([
                'precio_unitario' => $detalleProcesado['precio_unitario'],
                'subtotal' => $detalleProcesado['subtotal'],
            ]);
        }

        app(VentaService::class)->descontarInventario($this->cantidadesProcesadas);
        app(CajaService::class)->recalcularCajaAbierta($this->record->fecha_venta);

        // RF04: alertar si la venta dejó stock por debajo del mínimo.
        $bajoMinimo = app(VentaService::class)->productosBajoMinimo($this->cantidadesProcesadas);
        if ($bajoMinimo->isNotEmpty()) {
            Notification::make()
                ->warning()
                ->title('Stock bajo el mínimo')
                ->body('Quedaron bajo el mínimo: '.$bajoMinimo->map(fn ($p) => "{$p->nombre} ({$p->stock_actual} uds)")->join(', '))
                ->send();
        }
    }
}
