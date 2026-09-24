<x-filament-panels::page>
    <x-filament::section heading="Autorización para el tratamiento de datos personales" description="Ley 1581 de 2012 y Decreto 1377 de 2013">
        <div style="font-size:14px;line-height:1.6">
            <p style="margin-top:0">
                Para usar el sistema de MundoCoco registramos tu nombre, correo electrónico, rol y, en la auditoría,
                la dirección IP y el navegador desde el que realizas operaciones (ventas, cambios de inventario, cierres de caja).
            </p>
            <p>
                <strong>Finalidad:</strong> gestionar inventario, ventas y caja, garantizar la trazabilidad de las operaciones y la seguridad de la información.
                <strong>Derechos:</strong> puedes conocer, actualizar, rectificar y suprimir tus datos y revocar esta autorización escribiendo a
                <a href="mailto:{{ config('mundococo.contacto_datos') }}" style="text-decoration:underline">{{ config('mundococo.contacto_datos') }}</a>.
            </p>
            <p>
                Lee la <a href="{{ route('privacidad') }}" target="_blank" style="text-decoration:underline;font-weight:600">política completa de tratamiento de datos</a>.
            </p>

            @if (auth()->user()->aceptoPolitica())
                <p style="margin-bottom:0;opacity:.75">Aceptaste esta política el {{ auth()->user()->politica_aceptada_at->format('d/m/Y H:i') }}.</p>
            @else
                <x-filament::button wire:click="aceptar" style="margin-top:8px">
                    He leído y autorizo el tratamiento de mis datos
                </x-filament::button>
            @endif
        </div>
    </x-filament::section>
</x-filament-panels::page>
