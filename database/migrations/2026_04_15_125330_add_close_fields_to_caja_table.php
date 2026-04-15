<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caja', function (Blueprint $table) {
            $table->string('estado')->default('abierta')->after('fecha');
            $table->decimal('saldo_real', 10, 2)->nullable()->after('total_ventas');
            $table->decimal('diferencia', 10, 2)->nullable()->after('saldo_real');
            $table->timestamp('fecha_cierre')->nullable()->after('diferencia');
            $table->text('observaciones_cierre')->nullable()->after('observaciones');
        });
    }

    public function down(): void
    {
        Schema::table('caja', function (Blueprint $table) {
            $table->dropColumn([
                'estado',
                'saldo_real',
                'diferencia',
                'fecha_cierre',
                'observaciones_cierre',
            ]);
        });
    }
};
