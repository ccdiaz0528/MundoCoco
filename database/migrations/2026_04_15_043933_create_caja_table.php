<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('caja', function (Blueprint $table) {
        $table->id();
        $table->date('fecha');
        $table->decimal('saldo_inicial', 10, 2)->default(0);
        $table->decimal('total_efectivo', 10, 2)->default(0);
        $table->decimal('total_transferencias', 10, 2)->default(0);
        $table->decimal('total_tarjetas', 10, 2)->default(0);
        $table->decimal('total_ventas', 10, 2)->default(0);
        $table->text('observaciones')->nullable();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caja');
    }
};
