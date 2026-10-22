# AGENTS.md - MundoCoco

## Stack (verify in `composer.json`/`package.json`, not docs)
- Laravel `^13.0` + Filament `^5.0` + PHP `^8.4` + Sanctum `^4.0` (API tokens) + Pest `^4.5` (`pest-plugin-laravel`). Panel: `app/Providers/Filament/AdminPanelProvider.php` (`->databaseTransactions()`).
- Node 20+, Vite 8 + `@tailwindcss/vite` 4 + `laravel-vite-plugin` 3. Entrypoints `resources/css/app.css`, `resources/js/app.js`.
- DB: `.env.example` sets MySQL, but `config/database.php` defaults to sqlite; tests force `sqlite :memory:` (`phpunit.xml`). No external DB needed for tests.

## Setup & Run
```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
# .env: DB credentials + ADMIN_NAME/ADMIN_EMAIL/ADMIN_PASSWORD (initial Admin user via AdminUserSeeder)
php artisan migrate --seed
npm ci
npm run build
php artisan serve  # panel at /admin
```
- One-shot: `composer setup` does `install + .env copy + key:generate + migrate --force + npm install + build` (no `--seed`; run `php artisan migrate --seed` for the RF06 catalog).
- Existing DB after pulling: `php artisan migrate`, then `db:seed --class=MetodoPagoSeeder` (Nequi) and `--class=RoleSeeder` (RF10 permissions). All seeders are idempotent (`firstOrCreate`).
- Dev all-in-one: `composer dev` serves `serve` + `queue:listen --tries=1` + `vite` via `concurrently`.
- **Careful**: `php artisan install:*` commands run pending migrations against the `.env` database.
- Demo deploy (not production): `Dockerfile` + `docker/entrypoint.sh` (`migrate --force --seed` on boot, plus `schedule:work` so the daily backup fires in the demo) + `render.yaml`; every push to `main` redeploys https://mundococo-demo.onrender.com (Render + TiDB).

## Verification - run in this order (mirrors CI `quality.yml`)
```powershell
vendor/bin/pint --test   # style check, no fix; fix with vendor/bin/pint
composer test            # = config:clear + php artisan test
npm run build            # must pass; CI runs npm ci + build
```
- CI `.github/workflows/quality.yml` (push/PR): PHP 8.4 (pcov) + Node 20 → pint --test → tests → coverage `--min=80` via `phpunit.cobertura.xml` → npm build. But CI hasn't been runnable (account billing block, per README) - **local verification is the real gate**.
- Coverage scope (`phpunit.cobertura.xml`): only `app/{Services,Support,Observers,Policies,Http/Middleware,Models}` - Filament screens excluded. Local: `composer test:cobertura` (needs pcov/xdebug).
- No `pint.json`: Laravel preset, PSR-12, `.editorconfig` (4 spaces, LF).

## Single / focused test
```powershell
php artisan test --filter=VentaServiceTest
vendor/bin/pest --filter="puede crear una venta"
php artisan test tests/Feature/CumplimientoAnteproyectoTest.php
```
- Pest auto-applies `RefreshDatabase` to `Feature`+`Unit` (`tests/Pest.php`). No manual trait needed there.

## Architecture
- `app/Services/`: transactional rules - `VentaService::crear/actualizar/anular`, `CajaService::abrir/cerrar/recalcularCajaAbierta/totalesPorFecha`, `InventarioService::registrarInicial/adicionarStock/retirarStock/registrarConteo/stockCalculado`, `ReporteService` (`expresionFecha` per driver incl. sqlsrv), `ReporteExportService` (`TIPOS` = 11 reports × CSV/PDF/XLSX), `AuditService`. Keep business logic here, not in Filament Resources or Models. Sale CRUD goes ONLY through services: `CreateVenta/EditVenta` via `handleRecordCreation/handleRecordUpdate`, and `EditCaja` locks + recalculates via `recalcularCajaAbierta` - the `detalles` repeater does NOT use `->relationship()`.
- `app/Filament/Resources/*/{Schemas,Tables,Pages}/` (plural dirs: `Ventas`, `Cajas`, `Productos`, `Gastos`, `Movimientos`, `AuditLogs`, ...). New model needs `Resource.php` + `Schemas/*Form.php` + `Tables/*Table.php` + `Pages/List|Create|Edit.php`. Plus `Filament/Pages/{Reportes,ConteoFisico,ConsentimientoDatos}` and `Filament/Widgets/{StatsOverview,StockCritico}`. Filament 5 `TextInput` has no `->color()` (use `->hintColor()`).
- `app/Models/`: all `decimal:2` casts return strings (cast explicitly in tests). `Producto.codigo` unique, auto-generated in `booted()`; `Producto` uses SoftDeletes and historical relations (`VentaDetalle::producto`, `MovimientoInventario::producto`) are `withTrashed()` - and `VentaService::moverStock` restores stock with `Producto::withTrashed()` so a soft-deleted product still balances. `Venta::vigentes()` excludes annulled sales - **every sales total/report must use it**. `Venta/Caja/Gasto/MovimientoInventario` use `Concerns\PerteneceASucursal` (defaults to principal branch, RNF07).
- `app/Observers/`: `VentaObserver`/`GastoObserver` recalc the open `Caja`; `ProductoObserver` traces manual stock edits AND audits catalog field changes (`producto_actualizado`). All registered in `AppServiceProvider`. **Gotcha**: Laravel resolves a new observer instance per event (`Class@event`), so `updating`→`updated` state must be `static` (unset-on-read), never instance props.
- Money math lives in `App\Support\Dinero` (`aCentavos/desdeCentavos`); services mutate stock via query builder (no model events) so `ProductoObserver` only traces manual panel edits.
- `app/Policies/`: check **permissions** (`$user->can('ver ventas')`), never role names; `database/seeders/RoleSeeder.php` is the single source of truth (Admin = all; Operario = ventas + inventario read; Consultor = reportes). `User implements FilamentUser` - without it production returns 403 to everyone.
- Migrations: `2026_09_24_000001..000008` (Nequi, `precio_base`, Operador→Operario, anulación, soft deletes, `fecha_movimiento`, consentimiento, sucursales) and `2026_10_22_000001` unique index `caja_fecha_sucursal_unique` (throws on duplicate caja dates - fix data before migrating). Reference data (Nequi, permissions, catalog) comes from idempotent seeders, not migrations.
- Ley 1581: `App\Http\Middleware\ExigirConsentimientoDatos` redirects users without `politica_aceptada_at` to `ConsentimientoDatos`. `UserFactory` accepts by default; use `->sinConsentimiento()` to test the flow.
- `routes/web.php`: `/privacidad` view + `/login`→`/admin/login` + `/reportes/export/{tipo}/{formato}` via `ReporteExportService` (`auth` + `can:exportar reportes`; invalid `desde/hasta` → **422**, not redirect). `routes/api.php` (RNF07, read-only): `v1/{productos,ventas,reportes/{tipo}}`, `auth:sanctum` + `throttle:60,1` + `can:` per route; `/api/v1/ventas` is a paginated Laravel paginator (`data/total/per_page`; `por_pagina` 1-200, default 100, 422 beyond). Tokens via `php artisan mundococo:token-api {email}`; tokens expire per `SANCTUM_EXPIRATION` (minutes; default 30 days, 0 = never). `routes/console.php`: `backup:database` daily 02:00 (SQL dump to `storage/app/backups`, 30d retention) + weekly audit cleanup >365d; session lifetime 120 min.
- Timezone: `APP_TIMEZONE` (default `America/Bogota`, UTC-5). Caja is by date, so UTC would push sales after 7 p.m. into the next day; **do not revert**.
- `config/mundococo.php` (RNF06): `precio_venta_min/max` ($2.000-$95.000), `factor_reorden`, `contacto_datos`, `admin_*` - read these instead of hardcoding.

## Critical Business Rules (will break tests if ignored)
- **Server-validated prices (RF04)**: `precio_base` comes from DB; a line's `precio_unitario` may differ from base only within `config('mundococo.precio_venta_min/max')` (`VentaService::precioAplicado`), audited as `venta_precio_modificado`. Subtotal/total always computed server-side; compare money as strings, not floats. `fecha_venta` is user-set but never future.
- **Stock atomicity**: `lockForUpdate` + `DB::transaction(...,3)` in `VentaService::bloquearYValidarProductos`, `InventarioService::registrarLote` (row locks ordered by id), `CajaService::abrir/cerrar`. Duplicate `producto_id` lines are summed (`VentaService::agruparCantidades`) - insufficient aggregated stock throws `ValidationException`; on edit, quantities already in the sale count as available (`$reservadas`). Never allow negative stock.
- **One caja per date+sucursal, open or closed** (RF11 + unique index): `CajaService::abrir` rejects any existing caja for that date and catches MySQL 1062/SQLite UNIQUE as the race fallback. Closed caja blocks sales, edits and annulments (`VentaService::asegurarCajaNoCerrada`; `EditCaja` also refuses). Editable caja fields: only `fecha/saldo_inicial/observaciones`, then `recalcularCajaAbierta`.
- **Totals are never accepted from forms**: `CajaForm` marks every `total*`/`saldo_teorico` field `->dehydrated(false)` and recalculates client-side for display only.
- **Caja is by `fecha_venta`** (and `Gasto::fecha`), never `created_at`: `CajaService::totalesPorFecha` + `VentaObserver`, and only `vigentes()`.
- **Caja math (RF11)**: `saldo_teorico = saldo_inicial + total_ventas - total_gastos - total_retiros`; `diferencia = saldo_real - saldo_teorico` only when closing with counted cash. `total_*` split by `MetodoPago::NOMBRES` (Efectivo, **Nequi**, Transferencia, Tarjeta). Cash withdrawals are `Gasto` rows with `categoria = Gasto::RETIRO`: they lower `saldo_teorico` but go to `total_retiros`, never `total_gastos` (scopes `soloGastos/soloRetiros`).
- **Tracing RF12**: every stock change creates `MovimientoInventario` with `tipo` enum `inicial|compra|devolucion|ajuste_positivo|venta|ajuste_negativo|merma`, `fecha_movimiento` (transaction date) + `created_at` (exact timestamp), `stock_anterior/nuevo`, `user_id`, plus an `AuditService::logStockAjuste` entry. Sale edits log only the net difference per product, referencing the sale.
- **RF05**: `InventarioService::stockCalculado` counts movements since the product's last `inicial` - an `inicial` *sets* stock, it doesn't add; it's the latest `inicial` **by `fecha_movimiento`** (id as tiebreak), so a count registered later with a past date does not discard the movements after that date.
- **Conteo físico** (`Filament/Pages/ConteoFisico` → `InventarioService::registrarConteo`): counted − system becomes `ajuste_positivo/ajuste_negativo` (first count = `inicial`); uncounted products untouched; permission `registrar movimientos`.
- Sales/cajas never deleted. Correct with edit, or `VentaService::anular` (returns stock as `devolucion`, excluded via `vigentes()`, audited).

## Dominio (proyecto de grado - `RF y RNF.docx` manda)
- RF06: real catalog in `ProductoSeeder::CATALOGO` (store's physical price list), seeded idempotently with stock 0 and empty cost until the first physical count. Category names are exact (`Helados/Bebidas/Aceites/Productos de Coco`); `SimulacionRealMundoCocoTest` rejects `Postres/Ingredientes`. Do not rename.
- Conflicts between documents are resolved in `docs/CUMPLIMIENTO_ANTEPROYECTO.md` (4 payment methods incl. Nequi; price override with limits; rol `Operario`). Every new compliance feature adds its test in `tests/Feature/CumplimientoAnteproyectoTest.php`.
- RNF reflected in code: UI/comments/tests in Spanish (RNF02); operations <3s (RNF01); bcrypt + 2h session + audit + daily backup, Sanctum tokens expire by default (RNF04); parameters in config (RNF06); REST API paginated + 60 req/min (RNF09); export cells starting with `=+-@\t\r` are neutered with a `'` prefix (Excel formula injection); 80% coverage on critical code (RNF10).

## Testing Notes
- Pest + `RefreshDatabase` + `sqlite :memory:`. Factories exist for all models **except `Sucursal`** (create it directly / `Sucursal::principal()`). `CajaFactory`: one caja per date - historical cajas need distinct dates or the unique index fails the test. Do not assert hard counts without running the suite (`docs/TESTING_REPORT.md`).
- Key invariants to preserve (`docs/TESTING_REPORT.md`): no negative stock even with duplicate lines; client price only within config range and audited, subtotal/total never from the client; sales/edits/annulments on closed-caja dates rejected; caja totals only from server; `diferencia` only from counted cash at close.
- Livewire panel tests: `Filament::setCurrentPanel(Filament::getPanel('admin'))`. HTTP panel tests need a user with any role (FilamentUser) and an accepted data policy.
- SQLite stores `date` casts as `Y-m-d 00:00:00`: filter dates with `whereDate`, not `whereBetween` on date strings.

## References
- `README.md` (install + operational rules), `docs/MANUAL_TECNICO.md` (config, API, backup, deploy), `docs/TESTING_REPORT.md` (invariants), `docs/CUMPLIMIENTO_ANTEPROYECTO.md` (requirement → code → test matrix; read before changing business rules). Prefer executable config (`composer.json`, `phpunit*.xml`, `quality.yml`) when docs conflict.
- Requisitos autoritativos: `Anteproyecto - Mundo Coco.docx`, `RF y RNF.docx` (12 RF + 10 RNF), `Solución Sistema de Inventario y Facturación Mundo Coco.docx` - outside the repo at `C:\Users\Ryzen 5\Documents\MundoCoco - Documentación\03 Anteproyecto y requisitos`.
