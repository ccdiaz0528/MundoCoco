<?php

namespace App\Providers\Filament;

use Althinect\FilamentSpatieRolesPermissions\FilamentSpatieRolesPermissionsPlugin;
use App\Http\Middleware\ExigirConsentimientoDatos;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            // RF10 "cambiar contraseñas": cada usuario cambia la suya en su perfil
            // (el Administrador cambia la de otros desde Usuarios).
            ->profile(isSimple: false)
            // Create/Edit de recursos corren en una transacción: si algo falla
            // después de guardar, no quedan registros a medias.
            ->databaseTransactions()
            ->brandName('MundoCoco')            // ✅ nombre del panel
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,           // ✅ solo el widget de cuenta
                // FilamentInfoWidget eliminado  // ✅ quita el bloque de filament v5.5.0
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])

            // ->plugin(FilamentSpatieRolesPermissionsPlugin::make())

            ->authMiddleware([
                Authenticate::class,
                // Ley 1581: sin aceptar la política de datos no se opera el sistema.
                ExigirConsentimientoDatos::class,
            ])
            // Ley 1581: la política de tratamiento de datos es visible desde el login.
            ->renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, fn (): string => Blade::render(
                '<p style="text-align:center;font-size:12px;margin-top:12px;opacity:.75">Consulta la <a href="{{ route(\'privacidad\') }}" target="_blank" style="text-decoration:underline">política de tratamiento de datos personales</a> (Ley 1581 de 2012).</p>'
            ));
    }
}
