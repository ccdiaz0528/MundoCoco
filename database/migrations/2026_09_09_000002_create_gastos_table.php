<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // RF11: Control de caja diaria - gastos y compras del día
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->index();
            $table->string('descripcion');
            $table->string('categoria')->default('otros'); // materia_prima, servicios, transporte, otros
            $table->decimal('monto', 10, 2);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('caja_id')->nullable()->constrained('caja')->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['fecha', 'categoria']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
