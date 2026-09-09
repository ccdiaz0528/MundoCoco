<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // RNF04 Seguridad + Ley 1581/1377: logs de auditoría y habeas data
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion'); // created, updated, deleted, login, cierre_caja, venta, ajuste stock
            $table->string('modelo_type')->nullable();
            $table->unsignedBigInteger('modelo_id')->nullable();
            $table->json('cambios')->nullable();
            $table->json('valores_anteriores')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['modelo_type', 'modelo_id']);
            $table->index(['accion', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
