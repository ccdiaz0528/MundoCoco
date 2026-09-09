<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // RF02/RF03/RF12: tipos trazables
            $table->enum('tipo', ['inicial', 'compra', 'devolucion', 'ajuste_positivo', 'venta', 'ajuste_negativo', 'merma'])->index();
            $table->integer('cantidad'); // positiva para entradas, negativa conceptualmente pero guardamos positiva y tipo define signo
            $table->integer('stock_anterior');
            $table->integer('stock_nuevo');
            $table->string('motivo')->nullable();
            $table->text('observaciones')->nullable();
            // Referencia polimórfica opcional (venta, gasto, ajuste manual)
            $table->nullableMorphs('referencia');
            $table->timestamps();

            $table->index(['producto_id', 'created_at']);
            $table->index(['tipo', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
