<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ley 1581 de 2012 / Decreto 1377 de 2013: prueba del consentimiento
 * informado del titular (fecha en que aceptó la política de tratamiento).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('politica_aceptada_at')->nullable()->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('politica_aceptada_at');
        });
    }
};
