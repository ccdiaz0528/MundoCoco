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
  - `InventarioService`: `registrarInicial`, `adicionarStock`, `retirarStock`, `registrarLote`, `stockCalculado` (RF05).
  - `CajaService`: `abrir`, `cerrar`, `recalcularCajaAbierta`, `totalesPorFecha` (RF11).
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

### Actualizar una instalación existente

```powershell
php artisan migrate
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
