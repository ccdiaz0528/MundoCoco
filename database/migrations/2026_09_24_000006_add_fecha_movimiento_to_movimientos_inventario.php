<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RF02 "fecha de registro" y RF03 "fecha y hora de la transacción": la
 * indica el usuario. created_at sigue siendo la hora exacta del sistema (RF12).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->dateTime('fecha_movimiento')->nullable()->after('tipo');
            $table->index(['producto_id', 'fecha_movimiento']);
        });

        DB::table('movimientos_inventario')->whereNull('fecha_movimiento')->update(['fecha_movimiento' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->dropIndex(['producto_id', 'fecha_movimiento']);
            $table->dropColumn('fecha_movimiento');
        });
    }
};
