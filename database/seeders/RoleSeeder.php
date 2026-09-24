<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * RF10: roles y permisos. Las policies consultan PERMISOS (no nombres de
 * rol), así que este seeder es la única fuente de verdad del acceso.
 */
class RoleSeeder extends Seeder
{
    public const ADMIN = 'Admin';

    public const OPERARIO = 'Operario';

    public const CONSULTOR = 'Consultor';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Permisos granulares por módulo - RNF04 Seguridad + RF10
        $permisos = [
            // Productos RF01
            'ver productos', 'crear productos', 'editar productos', 'eliminar productos',
            // Categorías RF06
            'ver categorias', 'crear categorias', 'editar categorias', 'eliminar categorias',
            // Ventas RF04
            'ver ventas', 'crear ventas', 'editar ventas', 'anular ventas',
            // Caja RF11
            'ver caja', 'crear caja', 'editar caja', 'cerrar caja',
            // Métodos de pago
            'ver metodos_pago', 'gestionar metodos_pago',
            // Inventario RF02/RF03 + trazabilidad RF12
            'ver movimientos', 'registrar movimientos',
            // Gastos RF11
            'ver gastos', 'crear gastos', 'editar gastos', 'eliminar gastos',
            // Reportes RF07/RF08/RF09
            'ver reportes', 'exportar reportes',
            // Usuarios / Roles RF10
            'ver usuarios', 'gestionar usuarios',
            'ver roles', 'gestionar roles',
            // Auditoría RNF04 / Ley 1581
            'ver auditoria',
        ];

        foreach ($permisos as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        // Instalaciones previas: el rol se llamaba "Operador".
        Role::where('name', 'Operador')->where('guard_name', 'web')->update(['name' => self::OPERARIO]);

        $admin = Role::firstOrCreate(['name' => self::ADMIN, 'guard_name' => 'web']);
        $operario = Role::firstOrCreate(['name' => self::OPERARIO, 'guard_name' => 'web']);
        $consultor = Role::firstOrCreate(['name' => self::CONSULTOR, 'guard_name' => 'web']);

        // Administrador: acceso total al sistema.
        $admin->syncPermissions(Permission::all());

        // Operario: registro de ventas y consulta de inventario.
        $operario->syncPermissions([
            'ver ventas', 'crear ventas', 'editar ventas',
            'ver productos', 'ver categorias', 'ver movimientos',
        ]);

        // Consultor: solo visualización de reportes.
        $consultor->syncPermissions([
            'ver reportes', 'exportar reportes',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
