<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RF11: los retiros de efectivo (consignaciones, entregas al dueño) salen
 * de la caja pero no son gastos del negocio. Se guardan aparte para que el
 * saldo teórico cuadre sin inflar los gastos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caja', function (Blueprint $table) {
            $table->decimal('total_retiros', 10, 2)->default(0)->after('total_gastos');
        });
    }

    public function down(): void
    {
        Schema::table('caja', function (Blueprint $table) {
            $table->dropColumn('total_retiros');
        });
    }
};
