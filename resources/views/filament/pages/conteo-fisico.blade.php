<x-filament-panels::page>
    @php($datos = $this->getViewData())
    @php($pesos = fn ($valor) => '$ '.number_format((float) $valor, 0, ',', '.'))

    <style>
        .cf-scroll { overflow-x: auto; }
        .cf-tabla { width: 100%; border-collapse: collapse; font-size: 13px; }
        .cf-tabla th { padding: 8px 10px; border-bottom: 2px solid #e5e7eb; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; opacity: .65; text-align: left; }
        .cf-tabla td { padding: 6px 10px; border-bottom: 1px solid #f0f0f0; }
        .cf-tabla .num { text-align: right; white-space: nowrap; }
        .cf-tabla .grupo td { padding-top: 14px; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: #d97706; border-bottom: 1px solid #e5e7eb; }
        .cf-input { width: 90px; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; background: transparent; color: inherit; text-align: right; }
        .cf-label { display: block; font-size: 11px; font-weight: 600; margin-bottom: 4px; opacity: .7; }
        .cf-error { color: #dc2626; font-size: 12px; }
        .cf-pos { color: #16a34a; font-weight: 700; }
        .cf-neg { color: #dc2626; font-weight: 700; }
    </style>

    <p style="margin:0 0 4px;font-size:13px;opacity:.75">
        Reemplaza la planilla de inventario final. Cuente cada producto en la tienda y digite la cantidad;
        el sistema compara con su stock y registra los sobrantes y faltantes como ajustes trazables.
        Deje vacío lo que no contó: ese producto no se modifica.
    </p>

    <form wire:submit="registrar">
        <x-filament::section>
            <div style="display:flex;flex-wrap:wrap;gap:16px;align-items:flex-end">
                <div>
                    <label class="cf-label" for="cf-fecha">Fecha del conteo</label>
                    <input id="cf-fecha" type="date" wire:model="fecha" max="{{ now()->toDateString() }}" class="cf-input" style="width:auto;text-align:left" />
                    @error('fecha') <div class="cf-error">{{ $message }}</div> @enderror
                </div>
                <div style="flex:1;min-width:220px">
                    <label class="cf-label" for="cf-obs">Observaciones (responsable, turno…)</label>
                    <input id="cf-obs" type="text" wire:model="observaciones" maxlength="500" class="cf-input" style="width:100%;text-align:left" />
                </div>
                <label style="display:flex;gap:8px;align-items:center;font-size:13px">
                    <input type="checkbox" wire:model="esInicial" />
                    Es el inventario inicial de la tienda (fija el stock, sin calcular diferencias)
                </label>
            </div>
        </x-filament::section>

        <x-filament::section style="margin-top:16px">
            @error('conteos') <p class="cf-error" style="margin-top:0">{{ $message }}</p> @enderror
            <div class="cf-scroll">
                <table class="cf-tabla">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="num">Stock sistema</th>
                            <th class="num">Contado</th>
                            <th class="num">Diferencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($datos['porCategoria'] as $categoria => $productos)
                            <tr class="grupo"><td colspan="4">{{ $categoria }}</td></tr>
                            @foreach ($productos as $producto)
                                <tr wire:key="conteo-{{ $producto->id }}" x-data="{ v: '' }" x-on:conteo-registrado.window="v = ''">
                                    <td>{{ $producto->nombre }}</td>
                                    <td class="num">{{ $producto->stock_actual }}</td>
                                    <td class="num">
                                        <input type="number" min="0" step="1" inputmode="numeric" class="cf-input"
                                            aria-label="Conteo de {{ $producto->nombre }}"
                                            wire:model="conteos.{{ $producto->id }}"
                                            x-on:input="v = $event.target.value" />
                                        @error('conteos.'.$producto->id) <div class="cf-error">{{ $message }}</div> @enderror
                                    </td>
                                    <td class="num">
                                        <span x-show="v !== '' && ! $wire.esInicial"
                                            x-bind:class="(v - {{ $producto->stock_actual }}) > 0 ? 'cf-pos' : ((v - {{ $producto->stock_actual }}) < 0 ? 'cf-neg' : '')"
                                            x-text="(v - {{ $producto->stock_actual }}) > 0 ? '+' + (v - {{ $producto->stock_actual }}) : (v - {{ $producto->stock_actual }})"></span>
                                        <span x-show="v === '' || $wire.esInicial" style="opacity:.4">—</span>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr><td colspan="4" style="opacity:.65">No hay productos activos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div style="margin-top:16px">
                <x-filament::button type="submit" icon="heroicon-o-check-circle">
                    Registrar conteo
                </x-filament::button>
            </div>
        </x-filament::section>
    </form>

    @if ($resultado !== [])
        <x-filament::section
            :heading="$resultadoInicial ? 'Inventario inicial registrado' : 'Resultado del último conteo'"
            :description="$resultadoInicial ? 'Stock fijado con lo contado. Valor a precio de venta.' : 'Valorizado a precio de venta. Positivo = sobrante, negativo = faltante.'"
            style="margin-top:16px">
            <div class="cf-scroll">
                <table class="cf-tabla">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="num">Sistema</th>
                            <th class="num">Contado</th>
                            <th class="num">Diferencia</th>
                            <th class="num">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($resultado as $fila)
                            <tr>
                                <td>{{ $fila['nombre'] }}</td>
                                <td class="num">{{ $fila['sistema'] }}</td>
                                <td class="num">{{ $fila['contado'] }}</td>
                                <td class="num {{ $fila['diferencia'] > 0 ? 'cf-pos' : ($fila['diferencia'] < 0 ? 'cf-neg' : '') }}">{{ $fila['diferencia'] > 0 ? '+' : '' }}{{ $fila['diferencia'] }}</td>
                                <td class="num">{{ $pesos($fila['valor']) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="4" style="font-weight:700">{{ $resultadoInicial ? 'Valor del inventario contado' : 'Total de la diferencia' }}</td>
                            <td class="num" style="font-weight:800">{{ $pesos(collect($resultado)->sum('valor')) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
