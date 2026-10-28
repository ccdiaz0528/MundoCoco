<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Causa de la anulación (RF12 / RNF04). El motivo libre sigue explicando el
 * caso; la causa lo clasifica contra config('mundococo.motivos_anulacion')
 * para poder analizar anulaciones por motivo y detectar patrones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('categoria_anulacion', 50)->nullable()->after('motivo_anulacion');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('categoria_anulacion');
        });
    }
};
