<?php

namespace App\Http\Middleware;

use App\Filament\Pages\ConsentimientoDatos;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ley 1581 de 2012 / Decreto 1377 de 2013: antes de operar el sistema, cada
 * usuario debe aceptar la política de tratamiento de datos (consentimiento
 * informado). Mientras no la acepte, solo puede ver esa página.
 */
class ExigirConsentimientoDatos
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();
        $urlConsentimiento = ConsentimientoDatos::getUrl();

        $permitida = $request->url() === $urlConsentimiento || $request->routeIs('filament.*.auth.logout');

        if ($usuario && ! $usuario->aceptoPolitica() && ! $permitida) {
            return redirect()->to($urlConsentimiento);
        }

        return $next($request);
    }
}
