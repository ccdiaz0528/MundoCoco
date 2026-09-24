<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Política de Tratamiento de Datos Personales - MundoCoco</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 800px; margin: 40px auto; padding: 0 20px; line-height: 1.6; color: #222; font-size: 16px; }
        h1 { color: #92400e; font-size: 26px; }
        h2 { color: #78350f; margin-top: 28px; font-size: 19px; }
        .etiqueta { background: #fef3c7; color: #78350f; padding: 4px 8px; border-radius: 8px; font-size: 13px; }
        a { color: #92400e; }
    </style>
</head>
<body>
<main>
    <h1>MundoCoco - Política de Tratamiento de Datos Personales</h1>
    <p><span class="etiqueta">Ley 1581 de 2012 · Decreto 1377 de 2013 · Ley 1266 de 2008</span></p>

    <p><strong>Responsable del tratamiento:</strong> Tienda MundoCoco, Cali, Colombia.
        <strong>Canal de atención:</strong> <a href="mailto:{{ config('mundococo.contacto_datos') }}">{{ config('mundococo.contacto_datos') }}</a></p>

    <h2>1. Datos que se tratan</h2>
    <ul>
        <li>De los usuarios del sistema (empleados): nombre, correo electrónico, rol y contraseña cifrada.</li>
        <li>Registro de auditoría de operaciones: usuario, fecha y hora, dirección IP y navegador.</li>
        <li>Operaciones del negocio: ventas, métodos de pago, gastos, cajas y movimientos de inventario, asociados al usuario que los realizó.</li>
        <li>El sistema no recolecta datos personales de los clientes finales.</li>
    </ul>

    <h2>2. Finalidades</h2>
    <p>Gestionar el inventario, las ventas y el cuadre de caja de MundoCoco; mantener la trazabilidad de cada movimiento
        (quién, cuándo y qué); generar reportes operativos y financieros; y proteger la información mediante control de acceso,
        auditoría y copias de seguridad.</p>

    <h2>3. Derechos del titular (artículo 8, Ley 1581)</h2>
    <p>Conocer, actualizar, rectificar y solicitar la supresión de sus datos; solicitar prueba de la autorización otorgada;
        ser informado sobre el uso de sus datos; revocar la autorización; y presentar quejas ante la Superintendencia de
        Industria y Comercio. Las solicitudes se atienden en el canal indicado arriba, en los plazos del artículo 14 y 15 de la ley.</p>

    <h2>4. Autorización</h2>
    <p>Antes de operar el sistema por primera vez, cada usuario lee esta política y otorga su autorización expresa.
        El sistema guarda la fecha y hora de la aceptación como prueba del consentimiento (Decreto 1377, artículo 7).</p>

    <h2>5. Medidas de seguridad</h2>
    <ul>
        <li>Acceso solo con usuario y contraseña; las contraseñas se almacenan cifradas con bcrypt.</li>
        <li>La sesión se cierra tras 2 horas de inactividad.</li>
        <li>Permisos por rol: Administrador (acceso total), Operario (registro de ventas y consulta de inventario) y Consultor (solo reportes).</li>
        <li>Registro de auditoría de operaciones críticas (ventas, anulaciones, cambios de inventario, cierres de caja), conservado 365 días.</li>
        <li>Copia de seguridad automática diaria de la base de datos, conservada 30 días.</li>
    </ul>

    <h2>6. Información financiera y validez electrónica</h2>
    <p>Los registros de ventas, gastos y caja se tratan con los principios de la Ley 1266 de 2008. Los registros y reportes
        electrónicos del sistema tienen validez como mensajes de datos conforme a la Ley 527 de 1999.</p>

    <h2>7. Vigencia</h2>
    <p>Esta política rige desde su publicación y se mantiene mientras el sistema esté en operación. Los cambios sustanciales
        se informarán a los usuarios y requerirán una nueva autorización.</p>

    <p><em>Última actualización: 24 de septiembre de 2026.</em></p>
    <p><a href="/admin">← Volver al panel</a></p>
</main>
</body>
</html>
