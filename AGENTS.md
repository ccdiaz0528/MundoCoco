# AGENTS.md - MundoCoco

## Stack (verify in `composer.json`/`package.json`, not docs)
- Laravel `^13.0` + Filament `^5.0` + PHP `^8.3` + Pest `^4.5` (`pest-plugin-laravel`). `.agent.md` stating Laravel 11 is stale.
- Node 20+, Vite 8 + `@tailwindcss/vite` 4, `laravel-vite-plugin` 3. Entrypoints `resources/css/app.css`, `resources/js/app.js` (`vite.config.js:8`).
- DB: MySQL default (`.env.example:23`), tests force `sqlite :memory:` (`phpunit.xml:26-27`). No external DB needed for tests.

## Setup & Run
```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
# edit .env DB_CONNECTION/credentials (mysql) or use sqlite
php artisan migrate --seed
npm ci
npm run build
php artisan serve  # admin at /admin
```
- One-shot: `composer setup` (`composer.json:39-46`) does `install + .env copy + key:generate + migrate --force + npm install + build` (no `--seed`; use `php artisan migrate --seed` for RF06 categories/products).
- Dev all-in-one: `composer dev` (`composer.json:47-50`) runs `serve` + `queue:listen --tries=1` + `vite` via `concurrently`.
- Vite watches ignore `storage/framework/views/**` (`vite.config.js:15`).

## Verification - run in this order (mirrors CI `quality.yml`)
```powershell
vendor/bin/pint --test   # check style, no fix; fix with vendor/bin/pint
composer test            # = php artisan config:clear --ansi && php artisan test
npm run build            # must pass; CI runs npm ci + build
```
- CI `.github/workflows/quality.yml:25-37` on `push`/`pull_request`: PHP 8.3 + Node 20, `composer install` -> `pint --test` -> `php artisan test` -> `npm ci && npm run build`. All three must be green.
- Full style fix: `vendor/bin/pint` (no `pint.json` - uses Laravel preset, PSR-12, `.editorconfig` 4 spaces/LF, yaml 2 spaces).

## Single Test / Focused Run
```powershell
php artisan test --filter=VentaServiceTest
vendor/bin/pest --filter="puede crear una venta"
php artisan test tests/Unit/Services/CajaServiceTest.php
php artisan test --filter=CajasFlowTest
```
- Pest auto-applies `RefreshDatabase` to `Feature`+`Unit` (`tests/Pest.php:17-19`). No manual trait needed there; add it explicitly outside those dirs.

## Architecture
- `app/Services/`: transactional rules - `VentaService::crear/prepararDetalles/reconciliarEdicion/productosBajoMinimo`, `CajaService::abrir/cerrar/recalcularCajaAbierta/totalesPorFecha`, `InventarioService::registrarInicial/adicionarStock/retirarStock`, `ReporteService`, `ReporteExportService` (export CSV/PDF/XLSX vía `reportes.export`), `AuditService`. Keep business logic here, not in Filament Resources or Models.
- `app/Filament/Resources/*/{Schemas,Tables,Pages}/`: Filament 5 structure (plural dirs: `Ventas`, `Cajas`, `Productos`, `Gastos`, `Movimientos`, ...). New model needs `Resource.php` + `Schemas/*Form.php` + `Tables/*Table.php` + `Pages/List|Create|Edit.php`. Plus `Filament/Pages/Reportes.php` and `Filament/Widgets/StatsOverview.php`.
- `app/Models/`: `Venta`, `Caja`, `Producto`, `Categoria`, `MetodoPago`, `VentaDetalle`, `MovimientoInventario`, `Gasto`, `AuditLog` - all `decimal:2` casts return strings (cast to float/string explicitly in tests). `Producto.codigo` is unique, auto-generated in `booted()` when empty.
- `app/Observers/`: `VentaObserver` / `GastoObserver` / `ProductoObserver` recalculate open `Caja` on sale/gasto changes. Registered in `app/Providers/AppServiceProvider.php:28-30`.
- `app/Policies/` + `althinect/filament-spatie-roles-permissions` (`composer.json:10`): roles `Admin`/`Operador`/`Consultor` (read-only). Check `database/seeders/RoleSeeder.php` (29 granular permissions).
- `database/migrations/`: includes `2026_09_09_000001..000004` for `movimientos_inventario`, `gastos`, `add_gastos_to_caja`, `audit_logs`, plus `2026_09_08_000000_add_operational_indexes.php`.
- `routes/web.php:9,12,15`: `/privacidad` view + `/login`→`/admin/login` + `/reportes/export/{tipo}/{csv,pdf,xlsx}` via `ReporteExportService` (no controller); `routes/console.php:12` schedules real `backup:database` (SQL dump to `storage/app/backups`, 30d retention) + audit cleanup >365d; `config/session.php` (120 min lifetime).

## Critical Business Rules (will break tests if ignored)
- **Never trust browser prices**: `VentaService::crear` (`app/Services/VentaService.php:39`) re-reads `precio_venta` from DB, converts via `aCentavos():int` / `desdeCentavos():string` (`VentaService.php:281`). Compare money as strings (`decimal:2`), not floats.
- **Stock atomicity**: `lockForUpdate` + `DB::transaction(...,3)` in `VentaService::bloquearYValidarProductos` (`VentaService.php:205`) and `CajaService` (`CajaService.php:18`). Group duplicate `producto_id` via `agruparCantidades` (`VentaService.php:244`) - repeated lines sum, insufficient aggregated stock throws `ValidationException`.
- **Caja is by `fecha_venta`**, not `created_at`: `CajaService::totalesPorFecha` (`CajaService.php:110`) + `VentaObserver.php:16`. Closed caja blocks sales/edits: `asegurarCajaNoCerrada` (`VentaService.php:272`, `CajaService.php:50`).
- **Caja math RF11**: `saldo_teorico = saldo_inicial + total_ventas - total_gastos` (`CajaService.php:28,60,97`); `diferencia = saldo_real - saldo_teorico` only on `cerrar`. `total_*` split by `MetodoPago::EFECTIVO/TRANSFERENCIA/TARJETA` (`CajaService.php:113`).
- **Tracing RF12**: every stock change creates `MovimientoInventario` with `tipo` enum `inicial|compra|devolucion|ajuste_positivo|venta|ajuste_negativo|merma`, `stock_anterior/nuevo`, `user_id` (`VentaService.php:66`, `InventarioService.php:29`).
- Sales/cajas never deleted from panel; correct before close or via explicit annulment flow (`README.md:32`).

## Dominio (proyecto de grado - `DOCS DE PROYECTO/RF y RNF.docx` manda)
- RF06: 4 categorías con nombres exactos (`Helados/Bebidas/Aceites/Productos de Coco`, `CategoriaSeeder.php:12-30`); `SimulacionRealMundoCocoTest` rechaza `Postres/Ingredientes`. No renombrar.
- RF01: precios de venta $2.000–$95.000 enforced in `ProductoForm` (factories use synthetic values, exempt); `codigo` único autogenerado.
- Desviaciones doc→código (mantener código, los tests dependen de ello): RF04 permite precio distinto a la base y solo `Efectivo/Nequi`; el sistema impone precio de servidor y usa `Efectivo/Transferencia/Tarjeta` (`MetodoPago.php:13-17`).
- RNF que afectan código: interfaz y comentarios en español (RNF02); operaciones <3s (RNF01); bcrypt + sesión 2h + auditoría + backup diario (RNF04); 80% cobertura en funciones críticas (RNF10).

## Testing Notes
- Pest + `RefreshDatabase` + `sqlite :memory:`. Factories for all models exist (`database/factories/`). Do not assert hard counts without running suite - see `TESTING_REPORT.md:3`.
- Key invariants to preserve (`TESTING_REPORT.md:21-27`): no negative stock even with duplicate product lines; client cannot set price/subtotal/total; closed-date sales rejected; caja uses only `fecha_venta`; `diferencia` only from counted cash at close.

## References
- `README.md` (install + operational rules), `IMPLEMENTACION.md` (phase plan/gaps), `TESTING_REPORT.md` (invariants), `CUMPLIMIENTO_ANTEPROYECTO.md` (§10 declared doc→code deviations - read before changing business rules), `quality.yml` (CI contract). Requisitos autoritativos: `DOCS DE PROYECTO/RF y RNF.docx` (12 RF + 10 RNF) y `DOCS DE PROYECTO/Solución Sistema de Inventario y Facturación Mundo Coco.docx` (problema/objetivos). Prefer executable config (`composer.json` scripts, `phpunit.xml`, `vite.config.js`) when docs conflict.
