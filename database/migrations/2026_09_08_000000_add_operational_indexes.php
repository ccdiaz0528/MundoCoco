<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table): void {
            $table->index(['fecha_venta', 'metodo_pago_id'], 'ventas_fecha_metodo_index');
        });

        Schema::table('caja', function (Blueprint $table): void {
            $table->index(['fecha', 'estado'], 'caja_fecha_estado_index');
        });

        Schema::table('productos', function (Blueprint $table): void {
            $table->index(['activo', 'stock_actual'], 'productos_activo_stock_index');
        });

        Schema::table('metodos_pago', function (Blueprint $table): void {
            $table->index(['nombre', 'activo'], 'metodos_pago_nombre_activo_index');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table): void {
            $table->dropIndex('ventas_fecha_metodo_index');
        });

        Schema::table('caja', function (Blueprint $table): void {
            $table->dropIndex('caja_fecha_estado_index');
        });

        Schema::table('productos', function (Blueprint $table): void {
            $table->dropIndex('productos_activo_stock_index');
        });

        Schema::table('metodos_pago', function (Blueprint $table): void {
            $table->dropIndex('metodos_pago_nombre_activo_index');
        });
    }
};
