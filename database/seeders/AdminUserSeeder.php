<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Crea o actualiza el usuario administrador inicial.
 * Las credenciales SIEMPRE vienen del entorno (ver .env.example ADMIN_*);
 * sin ellas el seeder no crea nada, para nunca publicar claves por defecto.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('mundococo.admin_email');
        $password = (string) config('mundococo.admin_password');

        if ($email === '' || $password === '') {
            $this->command->warn('AdminUserSeeder omitido: defina ADMIN_EMAIL y ADMIN_PASSWORD en .env');

            return;
        }

        $usuario = User::firstOrNew(['email' => $email]);
        $usuario->name = (string) config('mundococo.admin_name');
        $usuario->password = Hash::make($password);
        $usuario->save();

        $usuario->syncRoles(['Admin']);

        $this->command->info("Administrador listo: {$email}");
    }
}
