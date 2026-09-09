<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caja', function (Blueprint $table) {
            $table->decimal('total_gastos', 10, 2)->default(0)->after('total_ventas');
            $table->decimal('saldo_teorico', 10, 2)->nullable()->after('total_gastos');
        });
    }

    public function down(): void
    {
        Schema::table('caja', function (Blueprint $table) {
            $table->dropColumn(['total_gastos', 'saldo_teorico']);
        });
    }
};
