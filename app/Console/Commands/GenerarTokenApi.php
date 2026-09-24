<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Console\Command;

/**
 * RNF07 integración: emite un token de la API de solo lectura para un
 * usuario. El token hereda los permisos RF10 del usuario y se muestra una
 * sola vez (en BD solo queda su hash).
 */
class GenerarTokenApi extends Command
{
    protected $signature = 'mundococo:token-api {email : Correo del usuario dueño del token} {--nombre=integracion : Nombre del sistema externo}';

    protected $description = 'Genera un token para la API de integración (solo lectura)';

    public function handle(): int
    {
        $usuario = User::where('email', $this->argument('email'))->first();

        if ($usuario === null || ! $usuario->roles()->exists()) {
            $this->error('No existe un usuario con ese correo y un rol asignado.');

            return self::FAILURE;
        }

        $token = $usuario->createToken((string) $this->option('nombre'));
        AuditService::log('token_api_emitido', $usuario, ['nombre' => $this->option('nombre')]);

        $this->info('Token generado (guárdelo ahora, no se volverá a mostrar):');
        $this->line($token->plainTextToken);

        return self::SUCCESS;
    }
}
