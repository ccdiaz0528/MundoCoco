@can('exportar reportes')
    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px">
        @foreach (['pdf' => 'PDF', 'xlsx' => 'Excel', 'csv' => 'CSV'] as $formato => $etiqueta)
            <a href="{{ route('reportes.export', ['tipo' => $tipo, 'formato' => $formato, 'desde' => $desde, 'hasta' => $hasta]) }}"
               style="padding:4px 10px;border-radius:6px;border:1px solid #d1d5db;font-size:12px;text-decoration:none;color:inherit">
                {{ $etiqueta }}
            </a>
        @endforeach
    </div>
@endcan
