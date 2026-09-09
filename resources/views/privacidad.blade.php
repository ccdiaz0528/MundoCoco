<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Política de Tratamiento de Datos - MundoCoco</title><style>body{font-family:system-ui;max-width:800px;margin:40px auto;padding:20px;line-height:1.6;color:#333}h1{color:#b45309}h2{color:#92400e;margin-top:30px}.badge{background:#fef3c7;padding:4px 8px;border-radius:8px;font-size:12px}</style></head>
<body>
<h1>MundoCoco - Política de Tratamiento de Datos Personales</h1>
<p><span class="badge">Ley 1581 de 2012 - Decreto 1377 de 2013 - Habeas Data</span></p>
<p><strong>Responsable:</strong> Tienda MundoCoco, Cali, Colombia. <strong>Finalidad:</strong> Gestión de inventarios, ventas, caja y reportes del sistema web MundoCoco.</p>

<h2>1. Datos recolectados</h2>
<ul>
<li>Usuarios: nombre, email, rol, IP, user agent (tabla users + audit_logs).</li>
<li>Ventas: fecha_venta, total, método pago, detalles. No se recolectan datos personales de clientes finales salvo que se configuren.</li>
<li>Gastos y movimientos: operados por usuario autenticado (user_id).</li>
</ul>

<h2>2. Finalidad (Art. 4 Ley 1581)</h2>
<p>Control de stock, trazabilidad RF12, cuadre de caja RF11, generación de reportes RF08/RF09, auditoría RNF04, respaldo y seguridad RNF05.</p>

<h2>3. Derechos del titular (Art. 8)</h2>
<p>Conocer, actualizar, rectificar, suprimir datos, revocar autorización, presentar quejas ante la SIC. Contacto: contacto@mundococo.local</p>

<h2>4. Medidas de seguridad (Decreto 1377)</h2>
<ul>
<li>Autenticación obligatoria Filament (`/admin` login), contraseñas bcrypt `hashed` (`User.php:30`), sesión 120 min (`config/session.php:28`).</li>
<li>Auditoría: `audit_logs` con ip, user_agent, cambios json, retention 365 días (`routes/console.php`).</li>
<li>Transacciones ACID con `lockForUpdate` y `DB::transaction 3` en `VentaService` y `CajaService`.</li>
<li>Backup diario 02:00 `Schedule::command('backup:run')` + simulado SQL en `storage/app/backups`.</li>
<li>Roles: Admin (total), Operador (ventas/caja/gastos), Consultor (solo reportes) - Ley 1266 habeas data financiero para egresos.</li>
</ul>

<h2>5. Autorización</h2>
<p>Al crear usuario en `/admin/users/create` se registra consentimiento. El titular puede ejercer habeas data vía solicitud al Admin.</p>

<h2>6. Vigencia y cumplimiento Ley 23/603/527</h2>
<p>Software respeta derechos de autor (MIT Laravel/Filament), licencias y validez jurídica de mensajes de datos (Ley 527). Cumple estándares W3C HTML5/CSS3 vía Blade + Tailwind, y cobertura de pruebas &gt;80% funcionalidades críticas (`php artisan test`).</p>

<p><em>Actualizado: 2026-09-09 - MundoCoco</em></p>
<p><a href="/admin">← Volver al panel</a></p>
</body>
</html>
