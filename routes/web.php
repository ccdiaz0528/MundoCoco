<?php

use App\Services\ReporteExportService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

Route::get('/', function () {
    return redirect('/admin');
});
Route::view('/privacidad', 'privacidad')->name('privacidad');

// La autenticación vive en el panel Filament; los invitados van allá.
Route::get('/login', fn () => redirect('/admin/login'))->name('login');

// Reportes exportación RF08/RF09 - CSV, PDF y Excel
// Es una descarga directa: un rango inválido responde 422 en vez de redirigir.
Route::get('/reportes/export/{tipo}/{formato}', function (string $tipo, string $formato, ReporteExportService $svc) {
    $rango = Validator::make(request()->only(['desde', 'hasta']), [
        'desde' => ['nullable', 'date_format:Y-m-d'],
        'hasta' => array_filter(['nullable', 'date_format:Y-m-d', request()->filled('desde') ? 'after_or_equal:desde' : null]),
    ]);
    abort_if($rango->fails(), 422, 'Rango de fechas inválido: use AAAA-MM-DD y "hasta" no anterior a "desde".');

    $desde = request('desde', now()->subDays(30)->toDateString());
    $hasta = request('hasta', now()->toDateString());

    return $svc->exportar($tipo, $formato, $desde, $hasta);
})->name('reportes.export')->middleware(['auth', 'can:exportar reportes']);
