# Manual Técnico — Sistema de Gestión MundoCoco

Documento de mantenimiento (RNF06). Para el uso diario ver `MANUAL_USUARIO.md`; para la trazabilidad de requisitos ver `CUMPLIMIENTO_ANTEPROYECTO.md`.

## 1. Tecnología

| Componente | Versión |
|---|---|
| PHP | 8.4 o superior (Symfony 8 en `composer.lock`) |
| Laravel | 13 |
| Panel administrativo | Filament 5 |
| Permisos | spatie/laravel-permission |
| API | Laravel Sanctum (tokens) |
| PDF / Excel | DomPDF / PhpSpreadsheet |
| Pruebas | Pest 4 (SQLite en memoria) |
| Base de datos | MySQL (producción); compatible con PostgreSQL, SQL Server y SQLite |

## 2. Arquitectura

```
Filament (pantallas)  ──►  Servicios (reglas de negocio)  ──►  Modelos Eloquent  ──►  BD
      │                        │
      └── Policies (RF10)      └── AuditService (RNF04)
```

- **`app/Services/`** contiene toda la lógica transaccional. Las pantallas solo delegan:
  - `VentaService`: `crear`, `actualizar`, `anular` (precio, stock, movimientos, caja y auditoría en una transacción).
  - `InventarioService`: `registrarInicial`, `adicionarStock`, `retirarStock`, `registrarLote`, `registrarConteo` (cierre con conteo físico: sobrante → `ajuste_positivo`, faltante → `ajuste_negativo`, o `inicial` en el primer conteo; usado por `Filament/Pages/ConteoFisico`, permiso `registrar movimientos`), `stockCalculado` (RF05).
  - `CajaService`: `abrir`, `cerrar`, `recalcularCajaAbierta`, `totalesPorFecha` (RF11). Saldo teórico = base + ventas − gastos − retiros; los retiros son `Gasto` con `categoria = Gasto::RETIRO` (consignaciones) y se acumulan en `caja.total_retiros`, no en `total_gastos`.
  - `ReporteService` / `ReporteExportService`: reportes RF08/RF09/RF11 e indicadores OE4; exportación PDF/Excel/CSV.
- **Dinero:** siempre en centavos enteros (`App\Support\Dinero`); se guarda como `decimal(10,2)`.
- **Stock:** los servicios lo modifican con el *query builder* y registran su propio `MovimientoInventario`; el `ProductoObserver` solo traza ajustes manuales hechos desde el formulario de producto.
- **Concurrencia:** `DB::transaction(…, 3)` + `lockForUpdate`, bloqueando productos en orden de `id`.
- **Permisos:** las policies consultan *permisos*, no nombres de rol. `database/seeders/RoleSeeder.php` es la única fuente de verdad.
- **Sucursales (RNF07):** ventas, cajas, gastos y movimientos tienen `sucursal_id` (trait `PerteneceASucursal`); por defecto, la sucursal principal.

## 3. Instalación

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
# Configurar DB_* y ADMIN_NAME / ADMIN_EMAIL / ADMIN_PASSWORD en .env
php artisan migrate --seed
npm ci
npm run build
```

El administrador inicial se crea con `ADMIN_EMAIL` y `ADMIN_PASSWORD`. Después del primer despliegue se puede borrar `ADMIN_PASSWORD` del `.env`: volver a ejecutar el seeder no cambia la contraseña de un usuario existente.

`ProductoSeeder` carga el catálogo real (50 productos con las tarifas de la planilla de la tienda, `ProductoSeeder::CATALOGO`) con stock 0 y costo vacío, y asigna el código porque `DatabaseSeeder` corre sin eventos de modelo. El stock inicial se registra desde *Catálogo → Conteo físico* en modo inventario inicial. Para dejar limpia una base de pruebas: `php artisan backup:database` y luego `php artisan migrate:fresh --seed --force`.

### Despliegue en producción

Después de instalar, optimizar y calentar el sistema antes de abrirlo al personal:

```powershell
php artisan optimize            # cachés de configuración, rutas, eventos y vistas
php artisan filament:optimize   # cachés de componentes e íconos de Filament
```

Luego conviene recorrer cada pantalla una vez con un usuario. En Windows, si varias personas piden a la vez la primera carga de una pantalla con las vistas aún sin compilar, el servidor puede responder errores 500 transitorios (renombrado simultáneo de las vistas compiladas); con las vistas compiladas no vuelve a ocurrir. Además, el panel limita el inicio de sesión a 5 intentos por minuto desde una misma IP.

Zona horaria: `APP_TIMEZONE=America/Bogota` (`config/app.php`; por defecto ya es Bogotá). La caja se cuadra por fecha de venta: en UTC, una venta después de las 7 p. m. quedaría en el día siguiente. El respaldo de las 02:00 también se ejecuta en hora de Cali. Los datos guardados antes de este ajuste en UTC no se convierten.

PHP: el proyecto exige 8.4 o superior (`composer.json`); se desarrolló con 8.5.10 y su suite y cobertura se verificaron también con 8.4.26. Active el programador de tareas del servidor (sección 6) para que el respaldo diario se ejecute.

### Actualizar una instalación existente

```powershell
php artisan migrate                            # incluye caja.total_retiros (2026_10_07_000001)
php artisan db:seed --class=MetodoPagoSeeder   # agrega Nequi sin duplicar
php artisan db:seed --class=RoleSeeder         # permisos estrictos de RF10 (renombra Operador → Operario)
```

## 4. Parámetros configurables (sin tocar código)

Definidos en `config/mundococo.php` y ajustables desde `.env`:

| Variable | Uso | Valor por defecto |
|---|---|---|
| `PRECIO_VENTA_MIN` / `PRECIO_VENTA_MAX` | Rango del precio de catálogo y del precio aplicado en una venta (RF01/RF04) | 2000 / 95000 |
| `FACTOR_REORDEN` | La sugerencia de reorden repone hasta `stock_minimo × factor` (RF07) | 2 |
| `CONTACTO_DATOS` | Canal de habeas data en la política de privacidad (Ley 1581) | datos@mundococo.com |
| `SESSION_LIFETIME` | Minutos de inactividad antes de cerrar la sesión (RNF04) | 120 |
| `ADMIN_*` | Administrador inicial | — |

## 5. Roles y permisos (RF10)

| Rol | Permisos |
|---|---|
| Admin | Todos |
| Operario | ver/crear/editar ventas, ver productos, ver categorías, ver movimientos |
| Consultor | ver reportes, exportar reportes |

Para crear un rol nuevo: *Seguridad → Roles*; las pantallas respetan los permisos asignados sin cambiar código.

## 6. Tareas programadas

`routes/console.php` (requiere `php artisan schedule:work` o un cron con `schedule:run`):

- `backup:database` diario a las 02:00: volcado SQL en `storage/app/backups`, retención de 30 días. En MySQL y SQLite incluye estructura y datos; en PostgreSQL y SQL Server solo datos (la estructura se recrea con `php artisan migrate`).
- Limpieza de auditoría con más de 365 días.

Para restaurar un respaldo de MySQL: `mysql -u USUARIO -p mundococo < storage/app/backups/backup-AAAA-MM-DD_HHMMSS.sql`.

## 7. API de integración (RNF07)

API REST de solo lectura con tokens Sanctum. Cada token hereda los permisos del usuario dueño.

```powershell
php artisan mundococo:token-api admin@mundococo.com --nombre=contabilidad
```

| Método y ruta | Permiso | Respuesta |
|---|---|---|
| `GET /api/v1/productos` | ver productos | Productos activos con stock y alerta |
| `GET /api/v1/ventas?desde=AAAA-MM-DD&hasta=AAAA-MM-DD` | ver ventas | Ventas con detalle, método de pago, sucursal y estado |
| `GET /api/v1/reportes/{tipo}?desde=…&hasta=…` | ver reportes | Cualquier reporte de `ReporteExportService::TIPOS` en JSON |

Encabezado: `Authorization: Bearer {token}`. Límite: 60 solicitudes por minuto.

## 8. Calidad

```powershell
vendor/bin/pint --test     # estilo
composer test              # pruebas
npm run build              # assets
composer test:cobertura    # RNF10: ≥ 80 % en código crítico (requiere pcov o xdebug)
```

CI (`.github/workflows/quality.yml`) ejecuta los cuatro pasos en cada push y pull request.

## 9. Diagnóstico

- Registro de la aplicación: `storage/logs/laravel.log`.
- Operaciones críticas: panel → *Informes → Auditoría* (solo Administrador).
- Consistencia del inventario: columna *Stock calculado* en Productos y el indicador *Precisión de inventario* en Reportes.
