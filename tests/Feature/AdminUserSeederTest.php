<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

describe('AdminUserSeeder', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
    });
    it('crea el administrador con rol Admin desde el entorno', function () {
        config()->set('mundococo.admin_email', 'admin@mundococo.test');
        config()->set('mundococo.admin_password', 'Clave-Segura-123');

        $this->seed(AdminUserSeeder::class);

        $usuario = User::where('email', 'admin@mundococo.test')->first();
        expect($usuario)->not->toBeNull()
            ->and($usuario->hasRole('Admin'))->toBeTrue()
            ->and(Hash::check('Clave-Segura-123', $usuario->password))->toBeTrue();
    });

    it('no crea nada sin credenciales en el entorno', function () {
        config()->set('mundococo.admin_email', '');
        config()->set('mundococo.admin_password', '');

        $this->seed(AdminUserSeeder::class);

        expect(User::count())->toBe(0);
    });

    it('es idempotente al re-ejecutar el seed', function () {
        config()->set('mundococo.admin_email', 'admin@mundococo.test');
        config()->set('mundococo.admin_password', 'Clave-Segura-123');

        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        expect(User::where('email', 'admin@mundococo.test')->count())->toBe(1);
    });
});
