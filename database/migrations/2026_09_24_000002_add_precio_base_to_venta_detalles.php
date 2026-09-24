<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RF04: el precio de venta puede diferir del precio base. Se guarda el
 * precio base vigente al vender para auditar descuentos o recargos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venta_detalles', function (Blueprint $table) {
            $table->decimal('precio_base', 10, 2)->nullable()->after('precio_unitario');
        });

        // Ventas históricas: se vendieron siempre al precio base.
        DB::table('venta_detalles')->whereNull('precio_base')->update(['precio_base' => DB::raw('precio_unitario')]);
    }

    public function down(): void
    {
        Schema::table('venta_detalles', function (Blueprint $table) {
            $table->dropColumn('precio_base');
        });
    }
};
