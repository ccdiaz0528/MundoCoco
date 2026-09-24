<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

/**
 * Aritmética monetaria exacta en centavos (int). Evita errores de
 * punto flotante: el dinero se compara y persiste como string decimal:2.
 */
trait Dinero
{
    /**
     * Convierte un valor monetario a centavos enteros.
     *
     * @throws ValidationException si el formato no es válido.
     */
    private function aCentavos(mixed $valor, string $campo = 'total'): int
    {
        $normalizado = str_replace(',', '.', trim((string) $valor));
        if (! preg_match('/^([0-9]+)(?:\.([0-9]{1,2}))?$/', $normalizado, $coincidencias)) {
            throw ValidationException::withMessages([$campo => 'El valor monetario no es válido.']);
        }

        return ((int) $coincidencias[1] * 100) + (int) str_pad($coincidencias[2] ?? '', 2, '0');
    }

    private function desdeCentavos(int $centavos): string
    {
        return sprintf('%d.%02d', intdiv($centavos, 100), abs($centavos % 100));
    }
}
