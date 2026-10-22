<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RF11: una sola caja por fecha y sucursal. El índice cierra la carrera entre
 * dos aperturas simultáneas (el chequeo del servicio no basta cuando la fila
 * todavía no existe) y evita duplicados al cambiar la fecha de una caja.
 */
return new class extends Migration
{
    public function up(): void
    {
        $duplicadas = DB::table('caja')
            ->selectRaw('sucursal_id, fecha, COUNT(*) as total')
            ->groupBy('sucursal_id', 'fecha')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicadas->isNotEmpty()) {
            throw new RuntimeException(
                'Hay cajas duplicadas por fecha; resuélvalas antes de crear el índice: '.
                $duplicadas->pluck('fecha')->implode(', ')
            );
        }

        Schema::table('caja', function (Blueprint $table): void {
            $table->unique(['fecha', 'sucursal_id'], 'caja_fecha_sucursal_unique');
        });
    }

    public function down(): void
    {
        Schema::table('caja', function (Blueprint $table): void {
            $table->dropUnique('caja_fecha_sucursal_unique');
        });
    }
};
