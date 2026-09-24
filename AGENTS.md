# AGENTS.md - MundoCoco

## Stack (verify in `composer.json`/`package.json`, not docs)
- Laravel `^13.0` + Filament `^5.0` + PHP 8.4+ (`composer.json` says `^8.3` but `composer.lock` pins Symfony 8, which needs 8.4) + Pest `^4.5` (`pest-plugin-laravel`) + Sanctum `^4.3` (API tokens).
- Node 20+, Vite 8 + `@tailwindcss/vite` 4, `laravel-vite-plugin` 3. Entrypoints `resources/css/app.css`, `resources/js/app.js` (`vite.config.js`).
- DB: MySQL default (`.env.example`), tests force `sqlite :memory:` (`phpunit.xml`). No external DB needed for tests.

## Setup & Run
```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
# edit .env DB_CONNECTION/credentials (mysql) or use sqlite
# also set ADMIN_NAME/ADMIN_EMAIL/ADMIN_PASSWORD (initial Admin user via AdminUserSeeder)
php artisan migrate --seed
npm ci
npm run build
php artisan serve  # admin at /admin
```
- One-shot: `composer setup` (`composer.json`) does `install + .env copy + key:generate + migrate --force + npm install + build` (no `--seed`; use `php artisan migrate --seed` for RF06 categories/products).
- Existing DB after pulling: `php artisan migrate`, then `php artisan db:seed --class=MetodoPagoSeeder` (Nequi) and `--class=RoleSeeder` (RF10 permissions). Seeders are idempotent.
- Dev all-in-one: `composer dev` (`composer.json`) runs `serve` + `queue:listen --tries=1` + `vite` via `concurrently`.
- Vite watches ignore `storage/framework/views/**` (`vite.config.js`).
- **Careful**: `php artisan install:*` commands run pending migrations against the `.env` database.

## Verification - run in this order (mirrors CI `quality.yml`)
```powershell
vendor/bin/pint --test   # check style, no fix; fix with vendor/bin/pint
composer test            # = php artisan config:clear --ansi && php artisan test
npm run build            # must pass; CI runs npm ci + build
```
- CI `.github/workflows/quality.yml` on `push`/`pull_request`: PHP 8.4 (pcov) + Node 20, `composer install` -> `pint --test` -> `php artisan test` -> coverage `--min=80` on critical code -> `npm ci && npm run build`.
- RNF10 coverage locally: `composer test:cobertura` (`phpunit.cobertura.xml`, critical dirs only). Needs pcov/xdebug - not installed in this dev environment.
- Full style fix: `vendor/bin/pint` (no `pint.json` - uses Laravel preset, PSR-12, `.editorconfig` 4 spaces/LF, yaml 2 spaces).

## Single Test / Focused Run
```powershell
php artisan test --filter=VentaServiceTest
vendor/bin/pest --filter="puede crear una venta"
php artisan test tests/Unit/Services/CajaServiceTest.php
php artisan test tests/Feature/CumplimientoAnteproyectoTest.php
```
- Pest auto-applies `RefreshDatabase` to `Feature`+`Unit` (`tests/Pest.php`). No manual trait needed there; add it explicitly outside those dirs.

## Architecture
- `app/Services/`: transactional rules - `VentaService::crear/actualizar/anular/productosBajoMinimo` (único camino: `CreateVenta`/`EditVenta` delegan vía `handleRecordCreation`/`handleRecordUpdate`; el repeater `detalles` NO usa `->relationship()`; panel con `->databaseTransactions()`), `CajaService::abrir/cerrar/recalcularCajaAbierta/totalesPorFecha`, `InventarioService::registrarInicial/adicionarStock/retirarStock/registrarLote/stockCalculado/inconsistenciasStock`, `ReporteService` (incl. `indicadores` OE4, `flujoCajaDiario`, `expresionFecha` per driver incl. sqlsrv), `ReporteExportService` (`TIPOS` = 11 reports × CSV/PDF/XLSX via `reportes.export`; same data as JSON in the API), `AuditService`. Keep business logic here, not in Filament Resources or Models.
- `app/Filament/Resources/*/{Schemas,Tables,Pages}/`: Filament 5 structure (plural dirs: `Ventas`, `Cajas`, `Productos`, `Gastos`, `Movimientos`, `AuditLogs`, ...). New model needs `Resource.php` + `Schemas/*Form.php` + `Tables/*Table.php` + `Pages/List|Create|Edit.php`. Plus `Filament/Pages/{Reportes,ConsentimientoDatos}.php` and `Filament/Widgets/{StatsOverview,StockCritico}.php`. Filament 5 `TextInput` has no `->color()` (use `->hintColor()`).
- `app/Models/`: `Venta`, `Caja`, `Producto`, `Categoria`, `MetodoPago`, `VentaDetalle`, `MovimientoInventario`, `Gasto`, `AuditLog`, `Sucursal` - all `decimal:2` casts return strings (cast to float/string explicitly in tests). `Producto.codigo` is unique, auto-generated in `booted()` when empty. `Producto` uses `SoftDeletes` (RF01 eliminar); historical relations (`VentaDetalle::producto`, `MovimientoInventario::producto`) are `withTrashed()`. `Venta::vigentes()` excludes annulled sales - **every sales total/report must use it**. `Venta/Caja/Gasto/MovimientoInventario` use trait `Concerns\PerteneceASucursal` (default = principal, RNF07).
- `app/Observers/`: `VentaObserver` / `GastoObserver` / `ProductoObserver` recalculate open `Caja` on sale/gasto changes. Registered in `app/Providers/AppServiceProvider.php`. **Gotcha**: Laravel resolves a new observer instance per event (`Class@event`), so `updating`→`updated` state must be `static` (with unset-on-read), never instance props.
- Money math lives in `App\Support\Dinero` (`aCentavos`/`desdeCentavos`); services must mutate stock via query-builder (no model events) so `ProductoObserver` only traces manual panel edits.
- `app/Policies/`: check **permissions** (`$user->can('ver ventas')`), never role names; `database/seeders/RoleSeeder.php` is the single source of truth. RF10 strict: `Admin` = all; `Operario` = ver/crear/editar ventas + ver productos/categorias/movimientos; `Consultor` = ver/exportar reportes. `User implements FilamentUser` (`canAccessPanel` = has any role) - without it production returns 403 to everyone.
- `database/migrations/`: `2026_09_24_000001..000008` add `caja.total_nequi`, `venta_detalles.precio_base`, role rename Operador→Operario, sale annulment (`ventas.anulada_at/anulada_por/motivo_anulacion`), `productos.deleted_at`, `movimientos_inventario.fecha_movimiento`, `users.politica_aceptada_at`, `sucursales` + `sucursal_id`. Reference data (Nequi, permissions) comes from idempotent seeders, not migrations.
- Ley 1581: `App\Http\Middleware\ExigirConsentimientoDatos` (panel auth middleware) redirects users without `politica_aceptada_at` to `ConsentimientoDatos`. `UserFactory` accepts by default; use `->sinConsentimiento()` to test the flow.
- `routes/web.php`: `/privacidad` view + `/login`→`/admin/login` + `/reportes/export/{tipo}/{csv,pdf,xlsx}` via `ReporteExportService` (middleware `auth` + `can:exportar reportes`; `desde`/`hasta` validated `Y-m-d`, invalid → 422). `routes/api.php` (RNF07): `/api/v1/{productos,ventas,reportes/{tipo}}`, `auth:sanctum` + `can:` per route; tokens via `php artisan mundococo:token-api {email}`. `routes/console.php` schedules real `backup:database` (SQL dump to `storage/app/backups`, 30d retention) + audit cleanup >365d; `config/session.php` (120 min lifetime).
- `config/mundococo.php` (RNF06): `precio_venta_min/max`, `factor_reorden`, `contacto_datos`, `admin_*` - read these instead of hardcoding.

## Critical Business Rules (will break tests if ignored)
- **Server-validated prices (RF04)**: `precio_base` always comes from DB; a line may carry a `precio_unitario` different from base only within `config('mundococo.precio_venta_min/max')` (`VentaService::precioAplicado`), audited as `venta_precio_modificado`. Subtotal/total are always computed server-side. Compare money as strings (`decimal:2`), not floats. `fecha_venta` is user-set but never future.
- **Stock atomicity**: `lockForUpdate` + `DB::transaction(...,3)` in `VentaService::bloquearYValidarProductos` (rows locked ordered by id), `InventarioService::registrarLote` (same order) and `CajaService::abrir/cerrar/recalcularCajaAbierta`. Group duplicate `producto_id` via `VentaService::agruparCantidades` - repeated lines sum, insufficient aggregated stock throws `ValidationException`. On edit, quantities already in the sale count as available (`$reservadas`).
- **Caja is by `fecha_venta`**, not `created_at`: `CajaService::totalesPorFecha` + `VentaObserver`. Closed caja blocks sales, edits and annulments: `VentaService::asegurarCajaNoCerrada` and `CajaService::cerrar`.
- **Caja math RF11**: `saldo_teorico = saldo_inicial + total_ventas - total_gastos` (`CajaService::abrir/cerrar/recalcularCajaAbierta`); `diferencia = saldo_real - saldo_teorico` only on `cerrar`. `total_*` split by `MetodoPago::NOMBRES` (Efectivo, **Nequi**, Transferencia, Tarjeta) in `totalesPorFecha` / `camposTotales`.
- **Tracing RF12**: every stock change creates `MovimientoInventario` with `tipo` enum `inicial|compra|devolucion|ajuste_positivo|venta|ajuste_negativo|merma`, `fecha_movimiento` (transaction date) + `created_at` (exact), `stock_anterior/nuevo`, `user_id`, and an `AuditService::logStockAjuste` entry for inventory changes. Sale edits log only the net difference per product (`venta` or `devolucion`), with `referencia_type/id` pointing to the sale.
- **RF05**: `InventarioService::stockCalculado` counts only movements since the product's last `inicial` (an `inicial` *sets* stock, it doesn't add).
- Sales/cajas never deleted. Correct with edit, or `VentaService::anular` (returns stock as `devolucion`, excluded via `vigentes()`, audited).

## Dominio (proyecto de grado - `DOCS DE PROYECTO/RF y RNF.docx` manda)
- RF06: 4 categorías con nombres exactos (`Helados/Bebidas/Aceites/Productos de Coco`, `CategoriaSeeder.php`); `SimulacionRealMundoCocoTest` rechaza `Postres/Ingredientes`. No renombrar.
- RF01: precios de venta en el rango de `config/mundococo.php` ($2.000–$95.000) en `ProductoForm` (factories use synthetic values, exempt; a sale at the base price is always accepted); `codigo` único autogenerado.
- Sin desviaciones declaradas: conflictos entre documentos resueltos en `docs/CUMPLIMIENTO_ANTEPROYECTO.md` §1 (4 métodos de pago con Nequi; precio editable con límites; rol `Operario`). Toda función nueva de cumplimiento agrega su prueba en `tests/Feature/CumplimientoAnteproyectoTest.php`.
- RNF que afectan código: interfaz y comentarios en español (RNF02); operaciones <3s (RNF01); bcrypt + sesión 2h + auditoría + backup diario (RNF04); parámetros en config (RNF06); 80% cobertura en funciones críticas (RNF10).

## Testing Notes
- Pest + `RefreshDatabase` + `sqlite :memory:`. Factories for all models exist (`database/factories/`). Do not assert hard counts without running suite - see `docs/TESTING_REPORT.md`.
- Key invariants to preserve (`docs/TESTING_REPORT.md`): no negative stock even with duplicate product lines; client price only within the configured range and audited, subtotal/total never from the client; closed-date sales/edits/annulments rejected; caja uses only `fecha_venta` and only `vigentes()`; `diferencia` only from counted cash at close.
- Livewire panel tests: `Filament::setCurrentPanel(Filament::getPanel('admin'))`. HTTP panel tests need a user with a role (FilamentUser) and an accepted data policy.
- SQLite stores `date` casts as `Y-m-d 00:00:00`: filter dates with `whereDate`, not `whereBetween` on date strings.

## References
- `README.md` (install + operational rules), `docs/MANUAL_TECNICO.md` (config, API, backup, roles), `docs/MANUAL_USUARIO.md`, `docs/IMPLEMENTACION.md` (phase plan), `docs/TESTING_REPORT.md` (invariants), `docs/CUMPLIMIENTO_ANTEPROYECTO.md` (requirement → code → test matrix; read before changing business rules), `quality.yml` (CI contract). Requisitos autoritativos: `DOCS DE PROYECTO/Anteproyecto - Mundo Coco.docx`, `DOCS DE PROYECTO/RF y RNF.docx` (12 RF + 10 RNF) y `DOCS DE PROYECTO/Solución Sistema de Inventario y Facturación Mundo Coco.docx`. Prefer executable config (`composer.json` scripts, `phpunit.xml`, `vite.config.js`) when docs conflict.
