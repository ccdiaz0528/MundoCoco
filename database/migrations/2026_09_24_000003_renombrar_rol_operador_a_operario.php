<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * RF10 nombra el rol "Operario". Renombra el rol existente conservando sus
 * usuarios; los permisos estrictos se aplican con:
 * php artisan db:seed --class=RoleSeeder
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->where('name', 'Operador')->update(['name' => 'Operario']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('roles')->where('name', 'Operario')->update(['name' => 'Operador']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
