<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RF04/RF11: total de ventas por Nequi separado en la caja
 * (Saldo = Base + Ventas_Efectivo + Ventas_Nequi + otros medios - Gastos).
 * El método "Nequi" lo crea MetodoPagoSeeder (idempotente) en bases existentes:
 * php artisan db:seed --class=MetodoPagoSeeder
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caja', function (Blueprint $table) {
            $table->decimal('total_nequi', 10, 2)->default(0)->after('total_efectivo');
        });
    }

    public function down(): void
    {
        Schema::table('caja', function (Blueprint $table) {
            $table->dropColumn('total_nequi');
        });
    }
};
