<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RF01: código único de producto (catálogo).
     * Nullable solo para permitir el relleno de filas preexistentes;
     * el modelo genera código automáticamente al crear (ver Producto::booted).
     * Reversible.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('codigo', 20)->nullable()->unique()->after('id');
        });

        // Códigos para filas preexistentes (P- + id con relleno).
        foreach (DB::table('productos')->select('id')->orderBy('id')->cursor() as $fila) {
            DB::table('productos')
                ->where('id', $fila->id)
                ->update(['codigo' => 'P-'.str_pad((string) $fila->id, 6, '0', STR_PAD_LEFT)]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropUnique(['codigo']);
            $table->dropColumn('codigo');
        });
    }
};
