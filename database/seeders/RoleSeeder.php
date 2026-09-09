<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Crear permisos granulares por módulo - RNF04 Seguridad + RF10
        $permisos = [
            // Productos
            'ver productos', 'crear productos', 'editar productos', 'eliminar productos',
            // Categorías
            'ver categorias', 'crear categorias', 'editar categorias', 'eliminar categorias',
            // Ventas
            'ver ventas', 'crear ventas', 'editar ventas', 'eliminar ventas',
            // Caja
            'ver caja', 'crear caja', 'editar caja', 'cerrar caja',
            // Métodos pago
            'ver metodos_pago', 'gestionar metodos_pago',
            // Movimientos / trazabilidad RF12
            'ver movimientos',
            // Gastos RF11
            'ver gastos', 'crear gastos', 'editar gastos',
            // Reportes RF08 RF09
            'ver reportes', 'exportar reportes',
            // Usuarios / Roles
            'ver usuarios', 'gestionar usuarios',
            'ver roles', 'gestionar roles',
            // Auditoría legal Ley 1581
            'ver auditoria',
        ];

        foreach ($permisos as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        // Roles RF10: Administrador, Operador, Consultor
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $operador = Role::firstOrCreate(['name' => 'Operador', 'guard_name' => 'web']);
        $consultor = Role::firstOrCreate(['name' => 'Consultor', 'guard_name' => 'web']);

        // Admin: todo
        $admin->syncPermissions(Permission::all());

        // Operador: registro ventas, consulta inventario, caja, gastos - sin gestión usuarios/roles ni auditoría
        $operador->syncPermissions([
            'ver productos', 'ver categorias', 'editar productos',
            'ver ventas', 'crear ventas', 'editar ventas',
            'ver caja', 'crear caja', 'editar caja', 'cerrar caja',
            'ver metodos_pago',
            'ver movimientos',
            'ver gastos', 'crear gastos', 'editar gastos',
            'ver reportes', 'exportar reportes',
        ]);

        // Consultor: solo visualización reportes + inventario (RF10)
        $consultor->syncPermissions([
            'ver productos', 'ver categorias',
            'ver ventas',
            'ver caja',
            'ver movimientos',
            'ver gastos',
            'ver reportes', 'exportar reportes',
        ]);
    }
}
