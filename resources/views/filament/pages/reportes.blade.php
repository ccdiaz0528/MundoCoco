<x-filament-panels::page>
    @php($datos = $this->getViewData())
    @php($val = $datos['valorizacion'])
    @php($flujo = $datos['flujo'])
    @php($ind = $datos['indicadores'])
    @php($rango = ['desde' => $datos['desde'], 'hasta' => $datos['hasta']])
    @php($pesos = fn ($valor) => '$ '.number_format((float) $valor, 0, ',', '.'))

    <style>
        .rpt-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px; margin-top: 16px; }
        .rpt-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-top: 16px; }
        .rpt-scroll { overflow-x: auto; }
        .rpt-scroll.alto { max-height: 320px; overflow-y: auto; }
        .rpt-tabla { width: 100%; border-collapse: collapse; font-size: 13px; }
        .rpt-tabla th { padding: 8px 10px; border-bottom: 2px solid #e5e7eb; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; opacity: .65; text-align: left; }
        .rpt-tabla td { padding: 9px 10px; border-bottom: 1px solid #f0f0f0; }
        .rpt-tabla .num { text-align: right; }
        .rpt-tabla .fuerte { font-weight: 700; }
        .rpt-kpi { margin: 0; font-size: 22px; font-weight: 800; }
        .rpt-vacio { margin: 0; font-size: 13px; opacity: .65; }
        .rpt-input { padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; background: transparent; color: inherit; }
        .rpt-label { display: block; font-size: 11px; font-weight: 600; margin-bottom: 4px; opacity: .7; }
    </style>

    <p style="margin:0 0 16px;font-size:13px;opacity:.75">
        Inventario, ventas y caja del {{ $datos['desde'] }} al {{ $datos['hasta'] }}. Ajusta el periodo y pulsa «Aplicar periodo».
    </p>

    <x-filament::section heading="Periodo del reporte" description="Filtra ventas, movimientos e indicadores por fecha. El inventario muestra el estado actual; el flujo de caja es del día «Hasta».">
        <form wire:submit="filtrar" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
            <div>
                <label class="rpt-label">Desde</label>
                <input type="date" wire:model="desde" class="rpt-input" />
            </div>
            <div>
                <label class="rpt-label">Hasta</label>
                <input type="date" wire:model="hasta" class="rpt-input" />
            </div>
            <button type="submit" style="padding:8px 16px;border-radius:8px;background:#d97706;color:#fff;font-size:13px;font-weight:600;border:none;cursor:pointer">Aplicar periodo</button>
        </form>
        @error('desde')
            <p style="margin:8px 0 0;font-size:12px;color:#dc2626">{{ $message }}</p>
        @enderror
        @error('hasta')
            <p style="margin:8px 0 0;font-size:12px;color:#dc2626">{{ $message }}</p>
        @enderror
    </x-filament::section>

    <div style="margin-top:16px">
        <x-filament::section heading="Valorización de inventario" description="Cantidad × precio de los {{ $val['count'] ?? 0 }} productos activos.">
            @include('filament.pages.partials.descargas', ['tipo' => 'valorizacion'] + $rango)
            <div class="rpt-kpis" style="margin-top:0">
                <div><span class="rpt-label">A costo</span><p class="rpt-kpi">{{ $pesos($val['total_costo'] ?? 0) }}</p></div>
                <div><span class="rpt-label">A precio de venta</span><p class="rpt-kpi">{{ $pesos($val['total_venta'] ?? 0) }}</p></div>
                <div><span class="rpt-label">Ganancia potencial</span><p class="rpt-kpi">{{ $pesos($val['ganancia_potencial'] ?? 0) }}</p></div>
                <div><span class="rpt-label">Productos en stock bajo</span><p class="rpt-kpi">{{ $datos['productosStockBajo']->count() }}</p></div>
            </div>
        </x-filament::section>
    </div>

    <div style="margin-top:16px">
        <x-filament::section heading="Indicadores de gestión" description="Precisión de inventario, pérdidas y exactitud del cuadre de caja en el periodo.">
            @include('filament.pages.partials.descargas', ['tipo' => 'indicadores'] + $rango)
            <div class="rpt-kpis" style="margin-top:0">
                <div><span class="rpt-label">Precisión de inventario</span><p class="rpt-kpi">{{ number_format($ind['precision_inventario']['porcentaje'], 1, ',', '.') }} %</p>
                    <span style="font-size:12px;opacity:.7">{{ $ind['precision_inventario']['inconsistentes'] }} de {{ $ind['precision_inventario']['productos'] }} con diferencia</span></div>
                <div><span class="rpt-label">Pérdidas (mermas y ajustes)</span><p class="rpt-kpi">{{ $pesos($ind['perdidas']['valor_costo']) }}</p>
                    <span style="font-size:12px;opacity:.7">{{ $ind['perdidas']['unidades'] }} unidades a costo</span></div>
                <div><span class="rpt-label">Productos agotados</span><p class="rpt-kpi">{{ $ind['agotados'] }}</p>
                    <span style="font-size:12px;opacity:.7">Riesgo de ventas perdidas</span></div>
                <div><span class="rpt-label">Cajas con diferencia</span><p class="rpt-kpi">{{ $ind['cuadre_caja']['con_diferencia'] }} / {{ $ind['cuadre_caja']['cerradas'] }}</p>
                    <span style="font-size:12px;opacity:.7">Faltante {{ $pesos($ind['cuadre_caja']['faltante_total']) }} · Sobrante {{ $pesos($ind['cuadre_caja']['sobrante_total']) }}</span></div>
                <div><span class="rpt-label">Ventas anuladas</span><p class="rpt-kpi">{{ $ind['anulaciones']['cantidad'] }}</p>
                    <span style="font-size:12px;opacity:.7">{{ $pesos($ind['anulaciones']['total']) }}</span></div>
            </div>
        </x-filament::section>
    </div>

    <div style="margin-top:16px">
        <x-filament::section heading="Inventario actual por categoría" description="Stock, productos en mínimo y valorización por línea.">
            @include('filament.pages.partials.descargas', ['tipo' => 'inventario'] + $rango)
            <div class="rpt-scroll">
                <table class="rpt-tabla">
                    <thead>
                        <tr><th>Categoría</th><th class="num">Productos</th><th class="num">Stock total</th><th class="num">En mínimo</th><th class="num">Val. costo</th><th class="num">Val. venta</th></tr>
                    </thead>
                    <tbody>
                        @forelse($datos['inventarioPorCategoria'] as $row)
                            <tr>
                                <td class="fuerte">{{ $row['categoria'] }}</td>
                                <td class="num">{{ $row['total_productos'] }}</td>
                                <td class="num">{{ $row['stock_total'] }}</td>
                                <td class="num fuerte">{{ $row['productos_bajo'] }}</td>
                                <td class="num">{{ $pesos($row['valorizacion_costo']) }}</td>
                                <td class="num fuerte">{{ $pesos($row['valorizacion_venta']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" style="text-align:center;opacity:.6">Sin categorías con productos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>

    <div style="margin-top:16px">
        <x-filament::section heading="Productos con stock bajo" description="Alerta en tiempo real con sugerencia de reorden.">
            @include('filament.pages.partials.descargas', ['tipo' => 'stock_bajo'] + $rango)
            @if($datos['productosStockBajo']->isEmpty())
                <p class="rpt-vacio">Sin alertas: todo el inventario está por encima del mínimo.</p>
            @else
                <div class="rpt-scroll">
                    <table class="rpt-tabla">
                        <thead>
                            <tr><th>Producto</th><th>Categoría</th><th class="num">Stock</th><th class="num">Mínimo</th><th class="num">Sugerencia</th></tr>
                        </thead>
                        <tbody>
                            @foreach($datos['productosStockBajo'] as $p)
                                <tr>
                                    <td class="fuerte">{{ $p->nombre }}</td>
                                    <td>{{ $p->categoria->nombre ?? '—' }}</td>
                                    <td class="num" style="font-weight:800">{{ $p->stock_actual }}</td>
                                    <td class="num">{{ $p->stock_minimo }}</td>
                                    <td class="num">Reponer {{ $p->sugerenciaReorden() }} uds</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    </div>

    <div class="rpt-grid">
        <x-filament::section heading="Comparativo con periodo anterior" description="Periodo {{ $datos['desde'] }} al {{ $datos['hasta'] }} vs anterior de igual duración.">
            @include('filament.pages.partials.descargas', ['tipo' => 'comparativo'] + $rango)
            <table class="rpt-tabla">
                <tbody>
                    <tr><td style="opacity:.7">Anterior ({{ $datos['comparativo']['periodo1']['transacciones'] }} ventas)</td><td class="num fuerte">{{ $pesos($datos['comparativo']['periodo1']['total']) }}</td></tr>
                    <tr><td style="opacity:.7">Seleccionado ({{ $datos['comparativo']['periodo2']['transacciones'] }} ventas)</td><td class="num fuerte">{{ $pesos($datos['comparativo']['periodo2']['total']) }}</td></tr>
                    <tr><td style="opacity:.7">Diferencia</td><td class="num fuerte">{{ $pesos($datos['comparativo']['diferencia']) }}</td></tr>
                    <tr><td class="fuerte">Variación</td><td class="num" style="font-weight:800">{{ number_format((float) $datos['comparativo']['variacion_porcentual'], 1, ',', '.') }} %</td></tr>
                </tbody>
            </table>
        </x-filament::section>

        <x-filament::section heading="Movimientos de inventario" description="Trazabilidad del {{ $datos['desde'] }} al {{ $datos['hasta'] }} ({{ $datos['movimientos']->count() }} movimientos).">
            @include('filament.pages.partials.descargas', ['tipo' => 'movimientos'] + $rango)
            @if($datos['movimientos']->isEmpty())
                <p class="rpt-vacio">Sin movimientos en el periodo seleccionado.</p>
            @else
                <div class="rpt-scroll alto">
                    <table class="rpt-tabla">
                        <thead>
                            <tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th class="num">Cant.</th><th class="num">Stock</th><th>Usuario</th></tr>
                        </thead>
                        <tbody>
                            @foreach($datos['movimientos'] as $m)
                                <tr>
                                    <td>{{ $m->fecha_movimiento?->format('d/m/Y H:i') }}</td>
                                    <td class="fuerte">{{ $m->producto->nombre ?? '—' }}</td>
                                    <td>{{ $m->tipo }}</td>
                                    <td class="num">{{ $m->cantidad }}</td>
                                    <td class="num">{{ $m->stock_anterior }} → {{ $m->stock_nuevo }}</td>
                                    <td>{{ $m->user->name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    </div>

    <div style="margin-top:16px">
        <x-filament::section heading="Ventas diarias por producto" description="{{ $datos['desde'] }} al {{ $datos['hasta'] }}">
            @include('filament.pages.partials.descargas', ['tipo' => 'ventas_producto'] + $rango)
            @if($datos['ventasDiariasPorProducto']->isEmpty())
                <p class="rpt-vacio">Sin ventas en el periodo seleccionado.</p>
            @else
                <div class="rpt-scroll alto">
                    <table class="rpt-tabla">
                        <thead>
                            <tr><th>Fecha</th><th>Producto</th><th class="num">Unidades</th><th class="num">Ingreso</th></tr>
                        </thead>
                        <tbody>
                            @foreach($datos['ventasDiariasPorProducto'] as $row)
                                <tr>
                                    <td>{{ $row->fecha }}</td>
                                    <td class="fuerte">{{ $row->producto }}</td>
                                    <td class="num">{{ (int) $row->cantidad }}</td>
                                    <td class="num fuerte">{{ $pesos($row->ingreso) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    </div>

    <div class="rpt-grid">
        <x-filament::section heading="Ventas por categoría" description="{{ $datos['desde'] }} al {{ $datos['hasta'] }}">
            @include('filament.pages.partials.descargas', ['tipo' => 'ventas_categoria'] + $rango)
            @if($datos['ventasPorCategoria']->isEmpty())
                <p class="rpt-vacio">Sin ventas en el periodo seleccionado.</p>
            @else
                <table class="rpt-tabla">
                    <thead><tr><th>Categoría</th><th class="num">Unidades</th><th class="num">Ingreso</th></tr></thead>
                    <tbody>
                        @foreach($datos['ventasPorCategoria'] as $row)
                            <tr>
                                <td class="fuerte">{{ $row['categoria'] }}</td>
                                <td class="num">{{ $row['cantidad_vendida'] }}</td>
                                <td class="num fuerte">{{ $pesos($row['ingreso_total']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-filament::section>

        <x-filament::section heading="Productos más vendidos" description="Top 5 del periodo seleccionado.">
            @include('filament.pages.partials.descargas', ['tipo' => 'mas_vendidos'] + $rango)
            @if($datos['productosMasVendidos']->isEmpty())
                <p class="rpt-vacio">Sin ventas en el periodo seleccionado.</p>
            @else
                <table class="rpt-tabla">
                    <thead><tr><th>Producto</th><th class="num">Unidades</th><th class="num">Ingreso</th></tr></thead>
                    <tbody>
                        @foreach($datos['productosMasVendidos'] as $d)
                            <tr>
                                <td class="fuerte">{{ $d->producto->nombre ?? '—' }}</td>
                                <td class="num">{{ $d->total_cantidad }}</td>
                                <td class="num fuerte">{{ $pesos($d->total_ingreso) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-filament::section>
    </div>

    <div class="rpt-grid">
        <x-filament::section heading="Ingresos por día" description="Evolución diaria en el periodo.">
            @include('filament.pages.partials.descargas', ['tipo' => 'ventas'] + $rango)
            @if($datos['ingresosPorDia']->isEmpty())
                <p class="rpt-vacio">Sin ventas en el periodo.</p>
            @else
                <table class="rpt-tabla">
                    <thead><tr><th>Fecha</th><th class="num">Total</th><th class="num">Ventas</th></tr></thead>
                    <tbody>
                        @foreach($datos['ingresosPorDia'] as $row)
                            <tr>
                                <td>{{ $row->periodo }}</td>
                                <td class="num fuerte">{{ $pesos($row->total) }}</td>
                                <td class="num">{{ $row->transacciones }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-filament::section>

        <x-filament::section heading="Flujo de efectivo del {{ $flujo['fecha'] }}" description="Caja {{ $flujo['caja'] }}. Saldo = Base + Ventas − Gastos.">
            @include('filament.pages.partials.descargas', ['tipo' => 'flujo_caja'] + $rango)
            <table class="rpt-tabla">
                <tbody>
                    <tr><td style="opacity:.7">Base de caja</td><td class="num fuerte">{{ $pesos($flujo['base']) }}</td></tr>
                    <tr><td style="opacity:.7">Efectivo</td><td class="num fuerte">{{ $pesos($flujo['ventas']['efectivo']) }}</td></tr>
                    <tr><td style="opacity:.7">Nequi</td><td class="num fuerte">{{ $pesos($flujo['ventas']['nequi']) }}</td></tr>
                    <tr><td style="opacity:.7">Transferencias</td><td class="num fuerte">{{ $pesos($flujo['ventas']['transferencias']) }}</td></tr>
                    <tr><td style="opacity:.7">Tarjetas</td><td class="num fuerte">{{ $pesos($flujo['ventas']['tarjetas']) }}</td></tr>
                    <tr><td style="opacity:.7">Total ventas ({{ $flujo['transacciones'] }})</td><td class="num" style="font-weight:800">{{ $pesos($flujo['ventas']['total']) }}</td></tr>
                    <tr><td style="opacity:.7">Gastos y compras ({{ $flujo['gastos_count'] }})</td><td class="num fuerte">{{ $pesos($flujo['gastos_total']) }}</td></tr>
                    <tr><td style="opacity:.7">Ventas anuladas ({{ $flujo['anuladas_count'] }})</td><td class="num">{{ $pesos($flujo['anuladas_total']) }}</td></tr>
                    <tr><td class="fuerte">Saldo del día</td><td class="num" style="font-weight:800">{{ $pesos($flujo['saldo_teorico']) }}</td></tr>
                    @if($flujo['diferencia'] !== null)
                        <tr><td style="opacity:.7">Contado al cierre / diferencia</td><td class="num fuerte">{{ $pesos($flujo['saldo_real']) }} / {{ $pesos($flujo['diferencia']) }}</td></tr>
                    @endif
                </tbody>
            </table>
        </x-filament::section>
    </div>
</x-filament-panels::page>
