<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Crea el usuario administrador inicial.
 * Las credenciales SIEMPRE vienen del entorno (ver .env.example ADMIN_*);
 * sin ellas el seeder no crea nada, para nunca publicar claves por defecto.
 * Si el usuario ya existe no se toca su nombre ni su contraseña (pudo
 * cambiarlos desde el panel): re-sembrar solo garantiza el rol Admin.
 * Tras el primer despliegue ADMIN_PASSWORD puede borrarse del .env.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('mundococo.admin_email');
        $password = (string) config('mundococo.admin_password');

        if ($email === '' || $password === '') {
            $this->command?->warn('AdminUserSeeder omitido: defina ADMIN_EMAIL y ADMIN_PASSWORD en .env');

            return;
        }

        $usuario = User::firstOrCreate(['email' => $email], [
            'name' => (string) config('mundococo.admin_name'),
            'password' => Hash::make($password),
        ]);

        // assignRole (no syncRoles): conserva otros roles que se le hayan dado.
        $usuario->assignRole('Admin');

        $this->command?->info($usuario->wasRecentlyCreated
            ? "Administrador creado: {$email}"
            : "Administrador existente conservado: {$email}");
    }
}
