@php
    use App\Support\AuditoriaLegible;

    $detalles = AuditoriaLegible::detalles($log);
    $hayAnteriores = collect($detalles)->contains(fn (array $fila): bool => $fila['antes'] !== '—');
@endphp

<style>
    .aud-detalle { font-size: 13px; color: inherit; }
    .aud-resumen { padding: 10px 12px; border-radius: 8px; background: rgba(128, 128, 128, .12); font-weight: 600; margin-bottom: 14px; }
    .aud-datos { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px 18px; margin-bottom: 16px; }
    .aud-datos dt { font-size: 11px; text-transform: uppercase; letter-spacing: .04em; opacity: .6; margin-bottom: 2px; }
    .aud-datos dd { margin: 0; word-break: break-word; }
    .aud-tabla { width: 100%; border-collapse: collapse; font-size: 13px; }
    .aud-tabla th { text-align: left; padding: 7px 10px; border-bottom: 2px solid rgba(128, 128, 128, .3); font-size: 11px; text-transform: uppercase; letter-spacing: .04em; opacity: .65; }
    .aud-tabla td { padding: 7px 10px; border-bottom: 1px solid rgba(128, 128, 128, .18); vertical-align: top; word-break: break-word; }
    .aud-tabla tr:last-child td { border-bottom: 0; }
    .aud-campo { font-weight: 600; }
    .aud-valor { opacity: .85; }
</style>

<div class="aud-detalle">
    <p class="aud-resumen">{{ AuditoriaLegible::resumen($log) }}</p>

    <dl class="aud-datos">
        <div>
            <dt>Acción</dt>
            <dd>{{ AuditoriaLegible::accion($log->accion) }}</dd>
        </div>
        <div>
            <dt>Usuario</dt>
            <dd>{{ $log->user?->name ?? 'Sistema' }}</dd>
        </div>
        <div>
            <dt>Fecha y hora</dt>
            <dd>{{ $log->created_at?->format('d/m/Y H:i:s') }}</dd>
        </div>
        @if ($log->modelo_type)
            <div>
                <dt>Registro</dt>
                <dd>{{ AuditoriaLegible::tipoRegistro($log) }} #{{ $log->modelo_id }}</dd>
            </div>
        @endif
        @if ($log->ip_address)
            <div>
                <dt>IP</dt>
                <dd>{{ $log->ip_address }}</dd>
            </div>
        @endif
    </dl>

    @if ($detalles === [])
        <p style="opacity:.7">Este registro no guarda un detalle de cambios.</p>
    @else
        <table class="aud-tabla">
            <thead>
                <tr>
                    <th>Campo</th>
                    @if ($hayAnteriores)
                        <th>Antes</th>
                    @endif
                    <th>Después</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($detalles as $fila)
                    <tr>
                        <td class="aud-campo">{{ $fila['campo'] }}</td>
                        @if ($hayAnteriores)
                            <td class="aud-valor">{{ $fila['antes'] }}</td>
                        @endif
                        <td class="aud-valor">{{ $fila['despues'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
