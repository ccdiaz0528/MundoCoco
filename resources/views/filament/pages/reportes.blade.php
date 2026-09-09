<x-filament-panels::page>
    @php($datos = $this->getViewData())
    @php($val = $datos['valorizacion'])
    @php($flujo = $datos['flujoHoy'])

    <p style="margin:0 0 16px;font-size:13px;opacity:.75">
        Inventario, ventas y caja del {{ $datos['desde'] }} al {{ $datos['hasta'] }}. Ajusta el periodo y pulsa «Aplicar periodo».
    </p>

    <x-filament::section heading="Periodo del reporte" description="Filtra las ventas por fecha. El inventario muestra el estado actual.">
        <form wire:submit="filtrar" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
            <div>
                <label style="display:block;font-size:11px;font-weight:600;margin-bottom:4px;opacity:.7">Desde</label>
                <input type="date" wire:model="desde" style="padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;background:transparent;color:inherit" />
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;margin-bottom:4px;opacity:.7">Hasta</label>
                <input type="date" wire:model="hasta" style="padding:8px 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;background:transparent;color:inherit" />
            </div>
            <div style="display:flex;gap:8px">
                <button type="submit" style="padding:8px 16px;border-radius:8px;background:#d97706;color:#fff;font-size:13px;font-weight:600;border:none;cursor:pointer">Aplicar periodo</button>
                <a href="{{ route('reportes.export', ['tipo' => 'inventario', 'formato' => 'csv']) }}?desde={{ $datos['desde'] }}&hasta={{ $datos['hasta'] }}" style="padding:8px 14px;border-radius:8px;border:1px solid #d1d5db;font-size:13px;text-decoration:none;color:inherit">Descargar inventario</a>
                <a href="{{ route('reportes.export', ['tipo' => 'ventas', 'formato' => 'csv']) }}?desde={{ $datos['desde'] }}&hasta={{ $datos['hasta'] }}" style="padding:8px 14px;border-radius:8px;border:1px solid #d1d5db;font-size:13px;text-decoration:none;color:inherit">Descargar ventas</a>
            </div>
        </form>
        @error('hasta')
            <p style="margin:8px 0 0;font-size:12px;color:#dc2626">{{ $message }}</p>
        @enderror
    </x-filament::section>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-top:16px">
        <x-filament::section heading="Valorización a costo" description="{{ $val['count'] ?? 0 }} productos activos">
            <p style="margin:0;font-size:22px;font-weight:800">$ {{ number_format((float) ($val['total_costo'] ?? 0), 0, ',', '.') }}</p>
        </x-filament::section>
        <x-filament::section heading="Valorización a venta" description="Valor potencial en vitrina">
            <p style="margin:0;font-size:22px;font-weight:800">$ {{ number_format((float) ($val['total_venta'] ?? 0), 0, ',', '.') }}</p>
        </x-filament::section>
        <x-filament::section heading="Ganancia potencial" description="Venta menos costo">
            <p style="margin:0;font-size:22px;font-weight:800">$ {{ number_format((float) ($val['ganancia_potencial'] ?? 0), 0, ',', '.') }}</p>
        </x-filament::section>
        <x-filament::section heading="Stock bajo" description="Requieren reposición">
            <p style="margin:0;font-size:22px;font-weight:800">{{ $datos['productosStockBajo']->count() }}</p>
        </x-filament::section>
    </div>

    <div style="margin-top:16px">
        <x-filament::section heading="Inventario actual por categoría" description="Stock, productos en mínimo y valorización por línea.">
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:13px">
                    <thead>
                        <tr>
                            <th align="left" style="padding:8px 10px;border-bottom:2px solid #e5e7eb;font-size:11px;text-transform:uppercase;letter-spacing:.04em;opacity:.65">Categoría</th>
                            <th align="right" style="padding:8px 10px;border-bottom:2px solid #e5e7eb;font-size:11px;text-transform:uppercase;letter-spacing:.04em;opacity:.65">Productos</th>
                            <th align="right" style="padding:8px 10px;border-bottom:2px solid #e5e7eb;font-size:11px;text-transform:uppercase;letter-spacing:.04em;opacity:.65">Stock total</th>
                            <th align="right" style="padding:8px 10px;border-bottom:2px solid #e5e7eb;font-size:11px;text-transform:uppercase;letter-spacing:.04em;opacity:.65">En mínimo</th>
                            <th align="right" style="padding:8px 10px;border-bottom:2px solid #e5e7eb;font-size:11px;text-transform:uppercase;letter-spacing:.04em;opacity:.65">Val. costo</th>
                            <th align="right" style="padding:8px 10px;border-bottom:2px solid #e5e7eb;font-size:11px;text-transform:uppercase;letter-spacing:.04em;opacity:.65">Val. venta</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($datos['inventarioPorCategoria'] as $row)
                            <tr>
                                <td style="padding:9px 10px;border-bottom:1px solid #f0f0f0;font-weight:600">{{ $row['categoria'] }}</td>
                                <td align="right" style="padding:9px 10px;border-bottom:1px solid #f0f0f0">{{ $row['total_productos'] }}</td>
                                <td align="right" style="padding:9px 10px;border-bottom:1px solid #f0f0f0">{{ $row['stock_total'] }}</td>
                                <td align="right" style="padding:9px 10px;border-bottom:1px solid #f0f0f0;font-weight:700">{{ $row['productos_bajo'] }}</td>
                                <td align="right" style="padding:9px 10px;border-bottom:1px solid #f0f0f0">$ {{ number_format((float) $row['valorizacion_costo'], 0, ',', '.') }}</td>
                                <td align="right" style="padding:9px 10px;border-bottom:1px solid #f0f0f0;font-weight:600">$ {{ number_format((float) $row['valorizacion_venta'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" style="padding:18px;text-align:center;opacity:.6">Sin categorías con productos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>

    <div style="margin-top:16px">
        <x-filament::section heading="Productos con stock bajo" description="Alerta en tiempo real con sugerencia de reposición.">
            @if($datos['productosStockBajo']->isEmpty())
                <p style="margin:0;font-size:13px">Sin alertas: todo el inventario está por encima del mínimo.</p>
            @else
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:13px">
                        <thead>
                            <tr>
                                <th align="left" style="padding:8px 10px;border-bottom:2px solid #e5e7eb;font-size:11px;text-transform:uppercase;letter-spacing:.04em;opacity:.65">Producto</th>
                                <th align="left" style="padding:8px 10px;border-bottom:2px solid #e5e7eb;font-size:11px;text-transform:uppercase;letter-spacing:.04em;opacity:.65">Categoría</th>
                                <th align="right" style="padding:8px 10px;border-bottom:2px solid #e5e7eb;font-size:11px;text-transform:uppercase;letter-spacing:.04em;opacity:.65">Stock</th>
                                <th align="right" style="padding:8px 10px;border-bottom:2px solid #e5e7eb;font-size:11px;text-transform:uppercase;letter-spacing:.04em;opacity:.65">Mínimo</th>
                                <th align="right" style="padding:8px 10px;border-bottom:2px solid #e5e7eb;font-size:11px;text-transform:uppercase;letter-spacing:.04em;opacity:.65">Sugerencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($datos['productosStockBajo'] as $p)
                                <tr>
                                    <td style="padding:9px 10px;border-bottom:1px solid #f0f0f0;font-weight:600">{{ $p->nombre }}</td>
                                    <td style="padding:9px 10px;border-bottom:1px solid #f0f0f0">{{ $p->categoria->nombre ?? '—' }}</td>
                                    <td align="right" style="padding:9px 10px;border-bottom:1px solid #f0f0f0;font-weight:800">{{ $p->stock_actual }}</td>
                                    <td align="right" style="padding:9px 10px;border-bottom:1px solid #f0f0f0">{{ $p->stock_minimo }}</td>
                                    <td align="right" style="padding:9px 10px;border-bottom:1px solid #f0f0f0">Reponer {{ max(0, $p->stock_minimo * 2 - $p->stock_actual) }} uds</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;margin-top:16px">
        <x-filament::section heading="Comparativo con periodo anterior" description="Periodo {{ $datos['desde'] }} al {{ $datos['hasta'] }} vs anterior de igual duración.">
            <table style="width:100%;border-collapse:collapse;font-size:13px">
                <tbody>
                    <tr><td style="padding:9px 10px;border-bottom:1px solid #f0f0f0;opacity:.7">Anterior ({{ number_format($datos['comparativo']['periodo1']['transacciones'], 0) }} ventas)</td><td align="right" style="padding:9px 10px;border-bottom:1px solid #f0f0f0;font-weight:700">$ {{ number_format((float) $datos['comparativo']['periodo1']['total'], 0, ',', '.') }}</td></tr>
                    <tr><td style="padding:9px 10px;border-bottom:1px solid #f0f0f0;opacity:.7">Seleccionado ({{ number_format($datos['comparativo']['periodo2']['transacciones'], 0) }} ventas)</td><td align="right" style="padding:9px 10px;border-bottom:1px solid #f0f0f0;font-weight:700">$ {{ number_format((float) $datos['comparativo']['periodo2']['total'], 0, ',', '.') }}</td></tr>
                    <tr><td style="padding:9px 10px;border-bottom:1px solid #f0f0f0;opacity:.7">Diferencia</td><td align="right" style="padding:9px 10px;border-bottom:1px solid #f0f0f0;font-weight:700">$ {{ number_format((float) $datos['comparativo']['diferencia'], 0, ',', '.') }}</td></tr>
                    <tr><td style="padding:9px 10px;font-weight:700">Variación</td><td align="right" style="padding:9px 10px;font-weight:800">{{ number_format((float) $datos['comparativo']['variacion_porcentual'], 1, ',', '.') }} %</td></tr>
                </tbody>
            </table>
        </x-filament::section>

        <x-filament::section heading="Movimientos de inventario" description="Trazabilidad del {{ $datos['desde'] }} al {{ $datos['hasta'] }} ({{ $datos['movimientos']->count() }} movimientos).">
            @if($datos['movimientos']->isEmpty())
                <p style="margin:0;font-size:13px;opacity:.65">Sin movimientos en el periodo seleccionado.</p>
            @else
                <div style="overflow-x:auto;max-height:320px;overflow-y:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:13px">
                        <thead>
                            <tr>
                                <th align="left" style="padding:8px 10px;border-bottom:2px solid #e5e7eb;font-size:11px;text-transform:uppercase;letter-spacing:.04em;opacity:.65">Fecha</th>
                                <th align="left" style="padding:8px 10px;border-bottom:2px solid #e5e7eb;font-size:11px;text-transform:uppercase;letter-spacing:.04em;opacity:.65">Producto</th>
                                <th align="left" style="padding:8px 10px;border-bottom:2px solid #e5e7eb;font-size:11px;text-transform:uppercase;letter-spacing:.04em;opacity:.65">Tipo</th>
                                <th align="right" style="padding:8px 10px;border-bottom:2px solid #e5e7eb;font-size:11px;text-transform:uppercase;letter-spacing:.04em;opacity:.65">Cant.</th>
                                <th align="right" style="padding:8px 10px;border-bottom:2px solid #e5e7eb;font-size:11px;text-transform:uppercase;letter-spacing:.04em;opacity:.65">Stock</th>
                                <th align="left" style="padding:8px 10px;border-bottom:2px solid #e5e7eb;font-size:11px;text-transform:uppercase;letter-spacing:.04em;opacity:.65">Usuario</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($datos['movimientos'] as $m)
                                <tr>
                                    <td style="padding:9px 10px;border-bottom:1px solid #f0f0f0">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                                    <td style="padding:9px 10px;border-bottom:1px solid #f0f0f0;font-weight:600">{{ $m->producto->nombre ?? '—' }}</td>
                                    <td style="padding:9px 10px;border-bottom:1px solid #f0f0f0">{{ $m->tipo }}</td>
                                    <td align="right" style="padding:9px 10px;border-bottom:1px solid #f0f0f0">{{ $m->cantidad }}</td>
                                    <td align="right" style="padding:9px 10px;border-bottom:1px solid #f0f0f0">{{ $m->stock_anterior }} → {{ $m->stock_nuevo }}</td>
                                    <td style="padding:9px 10px;border-bottom:1px solid #f0f0f0">{{ $m->user->name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;margin-top:16px">
        <x-filament::section heading="Ventas por categoría" description="{{ $datos['desde'] }} al {{ $datos['hasta'] }}">
            @if($datos['ventasPorCategoria']->isEmpty())
                <p style="margin:0;font-size:13px;opacity:.65">Sin ventas en el periodo seleccionado.</p>
            @else
                <table style="width:100%;border-collapse:collapse;font-size:13px">
                    <thead>
                        <tr>
                            <th align="left" style="padding:8px 10px;border-bottom:2px solid #e5e7eb;font-size:11px;text-transform:uppercase;letter-spacing:.04em;opacity:.65">Categoría</th>
                            <th align="right" style="padding:8px 10px;border-bottom:2px solid #e5e7eb;font-size:11px;text-transform:uppercase;letter-spacing:.04em;opacity:.65">Unidades</th>
                            <th align="right" style="padding:8px 10px;border-bottom:2px solid #e5e7eb;font-size:11px;text-transform:uppercase;letter-spacing:.04em;opacity:.65">Ingreso</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($datos['ventasPorCategoria'] as $row)
                            <tr>
                                <td style="padding:9px 10px;border-bottom:1px solid #f0f0f0;font-weight:600">{{ $row['categoria'] }}</td>
                                <td align="right" style="padding:9px 10px;border-bottom:1px solid #f0f0f0">{{ $row['cantidad_vendida'] }}</td>
                                <td align="right" style="padding:9px 10px;border-bottom:1px solid #f0f0f0;font-weight:600">$ {{ number_format((float) $row['ingreso_total'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-filament::section>

        <x-filament::section heading="Productos más vendidos" description="Top 5 del periodo seleccionado.">
            @if($datos['productosMasVendidos']->isEmpty())
                <p style="margin:0;font-size:13px;opacity:.65">Sin ventas en el periodo seleccionado.</p>
            @else
                <table style="width:100%;border-collapse:collapse;font-size:13px">
                    <thead>
                        <tr>
                            <th align="left" style="padding:8px 10px;border-bottom:2px solid #e5e7eb;font-size:11px;text-transform:uppercase;letter-spacing:.04em;opacity:.65">Producto</th>
                            <th align="right" style="padding:8px 10px;border-bottom:2px solid #e5e7eb;font-size:11px;text-transform:uppercase;letter-spacing:.04em;opacity:.65">Unidades</th>
                            <th align="right" style="padding:8px 10px;border-bottom:2px solid #e5e7eb;font-size:11px;text-transform:uppercase;letter-spacing:.04em;opacity:.65">Ingreso</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($datos['productosMasVendidos'] as $d)
                            <tr>
                                <td style="padding:9px 10px;border-bottom:1px solid #f0f0f0;font-weight:600">{{ $d->producto->nombre ?? '—' }}</td>
                                <td align="right" style="padding:9px 10px;border-bottom:1px solid #f0f0f0">{{ $d->total_cantidad }}</td>
                                <td align="right" style="padding:9px 10px;border-bottom:1px solid #f0f0f0;font-weight:600">$ {{ number_format((float) $d->total_ingreso, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-filament::section>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;margin-top:16px">
        <x-filament::section heading="Ingresos por día" description="Evolución diaria en el periodo.">
            @if($datos['ingresosPorDia']->isEmpty())
                <p style="margin:0;font-size:13px;opacity:.65">Sin movimientos en el periodo.</p>
            @else
                <table style="width:100%;border-collapse:collapse;font-size:13px">
                    <thead>
                        <tr>
                            <th align="left" style="padding:8px 10px;border-bottom:2px solid #e5e7eb;font-size:11px;text-transform:uppercase;letter-spacing:.04em;opacity:.65">Fecha</th>
                            <th align="right" style="padding:8px 10px;border-bottom:2px solid #e5e7eb;font-size:11px;text-transform:uppercase;letter-spacing:.04em;opacity:.65">Total</th>
                            <th align="right" style="padding:8px 10px;border-bottom:2px solid #e5e7eb;font-size:11px;text-transform:uppercase;letter-spacing:.04em;opacity:.65">Ventas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($datos['ingresosPorDia'] as $row)
                            <tr>
                                <td style="padding:9px 10px;border-bottom:1px solid #f0f0f0">{{ $row->periodo }}</td>
                                <td align="right" style="padding:9px 10px;border-bottom:1px solid #f0f0f0;font-weight:600">$ {{ number_format((float) $row->total, 0, ',', '.') }}</td>
                                <td align="right" style="padding:9px 10px;border-bottom:1px solid #f0f0f0">{{ $row->transacciones }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-filament::section>

        <x-filament::section heading="Flujo de caja de hoy" description="Ventas por método de pago, gastos y saldo del día.">
            <table style="width:100%;border-collapse:collapse;font-size:13px">
                <tbody>
                    <tr><td style="padding:9px 10px;border-bottom:1px solid #f0f0f0;opacity:.7">Efectivo</td><td align="right" style="padding:9px 10px;border-bottom:1px solid #f0f0f0;font-weight:700">$ {{ number_format((float) $flujo['ventas']['efectivo'], 0, ',', '.') }}</td></tr>
                    <tr><td style="padding:9px 10px;border-bottom:1px solid #f0f0f0;opacity:.7">Transferencias</td><td align="right" style="padding:9px 10px;border-bottom:1px solid #f0f0f0;font-weight:700">$ {{ number_format((float) $flujo['ventas']['transferencias'], 0, ',', '.') }}</td></tr>
                    <tr><td style="padding:9px 10px;border-bottom:1px solid #f0f0f0;opacity:.7">Tarjetas</td><td align="right" style="padding:9px 10px;border-bottom:1px solid #f0f0f0;font-weight:700">$ {{ number_format((float) $flujo['ventas']['tarjetas'], 0, ',', '.') }}</td></tr>
                    <tr><td style="padding:9px 10px;border-bottom:1px solid #f0f0f0;opacity:.7">Total ventas ({{ $flujo['transacciones'] }} ventas)</td><td align="right" style="padding:9px 10px;border-bottom:1px solid #f0f0f0;font-weight:800">$ {{ number_format((float) $flujo['ventas']['total'], 0, ',', '.') }}</td></tr>
                    <tr><td style="padding:9px 10px;border-bottom:1px solid #f0f0f0;opacity:.7">Gastos ({{ $flujo['gastos_count'] }})</td><td align="right" style="padding:9px 10px;border-bottom:1px solid #f0f0f0;font-weight:700">$ {{ number_format((float) $flujo['gastos_total'], 0, ',', '.') }}</td></tr>
                    <tr><td style="padding:9px 10px;font-weight:700">Saldo del día</td><td align="right" style="padding:9px 10px;font-weight:800">$ {{ number_format((float) $flujo['saldo_teorico'], 0, ',', '.') }}</td></tr>
                </tbody>
            </table>
        </x-filament::section>
    </div>
</x-filament-panels::page>
