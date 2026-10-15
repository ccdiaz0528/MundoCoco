<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\ReporteExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReporteController
{
    /** Reportes RF08/RF09/RF11 en JSON (mismos datos que PDF/Excel). */
    public function show(Request $request, string $tipo, ReporteExportService $exportador): array
    {
        abort_unless(array_key_exists($tipo, ReporteExportService::TIPOS), 404);

        $rango = Validator::validate($request->only(['desde', 'hasta']), [
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
        ]);

        [$encabezados, $filas] = $exportador->datos(
            $tipo,
            $rango['desde'] ?? now()->subDays(30)->toDateString(),
            $rango['hasta'] ?? now()->toDateString(),
        );

        return [
            'reporte' => ReporteExportService::TIPOS[$tipo],
            'columnas' => $encabezados,
            'filas' => $filas,
        ];
    }
}
