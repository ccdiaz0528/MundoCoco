<?php

namespace App\Filament\Pages;

use App\Services\AuditService;
use Filament\Pages\Page;

/**
 * Ley 1581 de 2012: consentimiento informado para el tratamiento de datos
 * personales. Queda la fecha en users.politica_aceptada_at y en auditoría.
 */
class ConsentimientoDatos extends Page
{
    protected static ?string $slug = 'consentimiento';

    protected static ?string $title = 'Tratamiento de datos personales';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.consentimiento-datos';

    public function aceptar(): void
    {
        $usuario = auth()->user();

        if (! $usuario->aceptoPolitica()) {
            $usuario->forceFill(['politica_aceptada_at' => now()])->save();
            AuditService::log('politica_datos_aceptada', $usuario, ['politica_aceptada_at' => $usuario->politica_aceptada_at->toDateTimeString()]);
        }

        $this->redirect(filament()->getUrl());
    }
}
