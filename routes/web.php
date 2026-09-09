<?php

use App\Services\ReporteExportService;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});
Route::view('/privacidad', 'privacidad')->name('privacidad');

// La autenticación vive en el panel Filament; los invitados van allá.
Route::get('/login', fn () => redirect('/admin/login'))->name('login');

// Reportes exportación RF08/RF09 - CSV, PDF y Excel
Route::get('/reportes/export/{tipo}/{formato}', function (string $tipo, string $formato, ReporteExportService $svc) {
    $desde = request('desde', now()->subDays(30)->toDateString());
    $hasta = request('hasta', now()->toDateString());

    return $svc->exportar($tipo, $formato, $desde, $hasta);
})->name('reportes.export')->middleware(['auth']);
