<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anulación/devolución de ventas (anteproyecto: "devoluciones registradas"
 * en el cuadre). La venta no se borra: queda marcada, devuelve el stock y
 * deja de sumar en caja y reportes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->timestamp('anulada_at')->nullable()->after('fecha_venta');
            $table->foreignId('anulada_por')->nullable()->after('anulada_at')->constrained('users')->nullOnDelete();
            $table->string('motivo_anulacion', 255)->nullable()->after('anulada_por');
            $table->index('anulada_at');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('anulada_por');
            $table->dropIndex(['anulada_at']);
            $table->dropColumn(['anulada_at', 'motivo_anulacion']);
        });
    }
};
