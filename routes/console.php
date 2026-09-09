<?php

use App\Models\AuditLog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// RNF04/RNF05 Seguridad y confiabilidad: respaldo diario 2am + limpieza logs.
// El comando backup:database genera un volcado SQL real en storage/app/backups
// (ver App\Console\Commands\BackupDatabase) y purga copias de más de 30 días.
Schedule::command('backup:database')->dailyAt('02:00')->onFailure(function () {
    logger()->warning('Backup diario falló');
});

// Limpieza auditoría > 365 días
Schedule::call(function () {
    AuditLog::where('created_at', '<', now()->subDays(365))->delete();
})->weekly();
