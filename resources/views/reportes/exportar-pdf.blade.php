<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }} - MundoCoco</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        p.meta { margin: 0 0 12px; color: #444; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 5px 7px; text-align: left; }
        th { background: #eee; }
        td.num { text-align: right; }
    </style>
</head>
<body>
    <h1>MundoCoco - {{ $titulo }}</h1>
    <p class="meta">Periodo: {{ $desde }} al {{ $hasta }} | Generado: {{ $generado }}</p>
    <table>
        <thead>
            <tr>
                @foreach($encabezados as $encabezado)
                    <th>{{ $encabezado }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($filas as $fila)
                <tr>
                    @foreach($fila as $i => $valor)
                        <td class="{{ $i > 0 ? 'num' : '' }}">{{ $valor }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($encabezados) }}">Sin datos en el periodo seleccionado.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
