# Estrategia de pruebas - MundoCoco

La suite usa **Pest 4.5**, **SQLite :memory:** y `RefreshDatabase` (`tests/Pest.php`, `phpunit.xml`). Última ejecución: **160 tests (566 assertions)**, 100% verde. No declarar cantidad vigente sin ejecutar `php artisan test`.

## Estado Actual 2026-09-24

```
PASS 160 tests (566 assertions)
vendor/bin/pint --test : verde (PSR-12)
```

## Cobertura de negocio (12 RF + 10 RNF)

### RF02/RF03/RF05 Inventario (7 tests)
- `InventarioServiceTest.php:7` : registrarInicial trazable (tipo inicial, stock_anterior/nuevo)
- Adicionar stock compra >0 validación, devolución trazable, merma valida insuficiente y retira
- RF05 `calcularStockTotal` = Inicial + Entradas - Salidas (20+10-5=25)
- `valorizacionInventario` por categoría (costo 2000, venta 3000) `ReporteService.php:12`

### RF11 Caja Diaria con Gastos (5 tests) - `CajaConGastosTest.php`
- saldo_teorico = Base + Ventas - Gastos (100k+80k-30k=150k)
- cierre con diferencia 0, detecta faltante -1000 y sobrante +1000
- bloquea gastos en caja cerrada (fecha_venta), totalesPorFecha incluye gastos segregados
- `CajaService.php:42` → `total_gastos` + `saldo_teorico`, `GastoObserver.php:10` recalcula

### RF12 Trazabilidad Completa (4 tests) - `TrazabilidadTest.php`
- venta genera `MovimientoInventario` tipo venta con stock antes/después y user_id (`VentaService.php:57`)
- historial filtra por producto/fecha/tipo vía `InventarioService::historial`
- no negativo con líneas duplicadas (stock 5, intento 3+3 falla, mantiene 5 y 1 inicial)
- creación producto registra inicial automáticamente (`ProductoObserver.php:10`)

### RF08/RF09 Reportes (5 tests) - `ReportesServiceTest.php`
- inventarioPorCategoria: Helados 2 prods (1 bajo), Bebidas stock 20
- productosStockBajo: 2 detectados
- ventasDiariasPorProducto: 2 prods en fecha
- productosMasVendidos: ordenado 10 vs 1
- ingresosPorPeriodo día: 2 periodos, 200 ayер

### Simulación Real MundoCoco (3 tests) - `SimulacionRealMundoCocoTest.php`
- **Jornada completa** : categorías RF06 exactas (Helados/Bebidas/Aceites/Coco), 3 productos (Arequipe 30, Brownie 15, Limonada 25), inicial trazado, apertura caja 100k, compra +10 Brownie (25), 15 ventas (5 efectivo 22500, 5 transfer 50000, 5 tarjeta 20000 =92500), gasto 20k, totales segregados, cierre esperado 172500 diferencia 0, bloquea venta tras cierre, tiempo <3s RNF01, movimientos ≥19
- categorías RF06 validan 4 nombres exactos (rechaza Postres/Ingredientes)
- roles RF10 Admin/Operario/Consultor, Consultor no auditoría pero sí reportes

### Base Previa (60 tests)
- `CajaTest` 6: crear caja, abierta saldo_real = inicial, cerrar, totales por método, diferencia
- `VentaServiceTest` 3: precio confiable servidor, rechaza stock acumulado duplicado, rechaza caja cerrada
- `CajaServiceTest` 2: impide doble caja fecha, cierra con diferencia desde fecha_venta
- `VentaObserverTest` 2: recalcula importes exactos fecha_venta, quita venta eliminada
- Modelos: `ProductoTest` 6, `VentaTest` 6, `VentaDetalleTest` 5, `CategoriaTest` 4, `MetodoPagoTest` 4
- `CajasFlowTest` 7, `InventarioFlowTest` 9, `VentasFlowTest` 5

## Comandos (CI `quality.yml`)

```powershell
composer install
vendor/bin/pint --test   # PSR-12, sin fix
composer test            # php artisan config:clear && php artisan test
composer test:cobertura  # RNF10 (requiere pcov o xdebug)
npm ci; npm run build    # Vite 8 + Tailwind 4
```

CI: PHP 8.4 (pcov) + Node 20, `composer install` -> `pint --test` -> `php artisan test` -> cobertura `--min=80` -> `npm ci && npm run build`

## Casos Críticos que Deben Mantenerse (Invariantes Anteproyecto)

- Venta nunca deja stock negativo, incluso con líneas duplicadas agrupadas (`VentaService::bloquearYValidarProductos`, `agruparCantidades`)
- Precio (RF04): el precio base sale de la BD; un precio distinto solo se acepta dentro de `config('mundococo.precio_venta_min/max')` y queda auditado. Subtotal y total nunca vienen del cliente.
- Ventas, ediciones y anulaciones de fecha con caja cerrada rechazadas (`VentaService::asegurarCajaNoCerrada`, `GastoPolicy`)
- Caja usa exclusivamente `fecha_venta` (y `Gasto::fecha`), no `created_at`, y solo ventas `vigentes()` (no anuladas)
- Diferencia solo con dinero contado al cierre (`CajaService::cerrar`)
- RF11: `saldo_teorico = saldo_inicial + total_ventas - total_gastos`, con Nequi separado en `total_nequi`
- Trazabilidad completa: todo cambio de stock crea `MovimientoInventario` (tipo, fecha de transacción, cantidad, stock antes/después, usuario) y un registro de auditoría
- RF05: el stock calculado cuenta desde el último inventario inicial y debe coincidir con `stock_actual`
- RF10: las policies consultan permisos; Operario solo ventas + consulta de inventario, Consultor solo reportes
- Ley 1581: sin `politica_aceptada_at` no se opera el panel

## Pruebas de cumplimiento del anteproyecto (2026-09-24)

`tests/Feature/CumplimientoAnteproyectoTest.php` agrupa una prueba por brecha cerrada (Nequi, precio y fecha de venta, roles RF10, anulación, borrado lógico, RF02/RF03/RF05, reportes e indicadores OE4, Ley 1581, RNF07 sucursal y API, humo de todas las pantallas del panel). La matriz requisito → código → prueba está en `docs/CUMPLIMIENTO_ANTEPROYECTO.md`.

RNF10: la cobertura se mide con `composer test:cobertura` (`phpunit.cobertura.xml`, solo código crítico, mínimo 80 %) en CI con pcov; el entorno local no tiene driver de cobertura.

## Nuevos Tests Añadidos 2026-09-09

- `tests/Unit/Services/InventarioServiceTest.php` (7)
- `tests/Feature/CajaConGastosTest.php` (5)
- `tests/Feature/TrazabilidadTest.php` (4)
- `tests/Feature/ReportesServiceTest.php` (5 + 2: comparativo y movimientos por período)
- `tests/Feature/SimulacionRealMundoCocoTest.php` (3)

## Cierre de Cumplimiento Estricto 2026-09-09 (Fase 8)

- `tests/Unit/Models/ProductoCodigoTest.php` (4): RF01 código único autogenerado, manual y rechazo de duplicados
- `tests/Unit/Services/VentaAlertaStockTest.php` (4): RF04 `productosBajoMinimo()` tras venta real y casos vacíos
- `tests/Feature/ReporteExportTest.php` (5): RF08/RF09 export CSV/PDF/XLSX (magic bytes `%PDF`/`PK`), 404 y auth
- `tests/Feature/BackupDatabaseTest.php` (2): RNF04 volcado SQL real + purga por retención
- `tests/Feature/RendimientoTest.php` (2): RNF01 200 productos, reportes <3s
- `tests/Feature/AdminUserSeederTest.php` (3): creación con rol, omisión sin credenciales, idempotencia

## Endurecimiento 2026-09-09 (Fase 9)

- `tests/Unit/Observers/ProductoObserverTest.php` (3): ajuste manual +/− trazado, sin movimiento al editar otros campos
- `tests/Unit/Observers/GastoObserverTest.php` (2): recalcula al crear y al cambiar fecha sin errores
- Cobertura nueva en `ReportesServiceTest.php`: ventas por categoría agregadas en SQL

## Cobertura RNF10

Histórico: hasta el 2026-09-09 el CI usaba `coverage: none` y la cobertura nunca se midió. Desde el 2026-09-24 el CI instala pcov y exige `--min=80` sobre el código crítico (`phpunit.cobertura.xml`). Localmente no hay driver de cobertura.

## Métricas RF/RNF vs Tiempo

- RNF01 <3s: simulación jornada 0.09s (incluye 15 ventas + cierre)
- RNF04 backup diario 02:00 + audit 365d retención (`routes/console.php:10`)
- RNF10 80% cobertura: se mide en CI (pcov) con `phpunit.cobertura.xml`; no hay una cifra medida localmente
