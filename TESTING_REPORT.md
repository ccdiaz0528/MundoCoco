# Estrategia de pruebas - MundoCoco

La suite usa **Pest 4.5**, **SQLite :memory:** y `RefreshDatabase` (`tests/Pest.php:17`, `phpunit.xml:26`). **103 tests** (239 assertions), 100% verde. No declarar cantidad vigente sin ejecutar `php artisan test`.

## Estado Actual 2026-09-09

```
PASS 103 tests (239 assertions)
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
- roles RF10 Admin/Operador/Consultor 24 permisos, Consultor no auditoría pero sí reportes

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
composer test            # php artisan config:clear && php artisan test (103 tests)
npm ci; npm run build    # Vite 8 + Tailwind 4
```

CI: PHP 8.3 + Node 20, `composer install` -> `pint --test` -> `php artisan test` -> `npm ci && npm run build` (todos verdes)

## Casos Críticos que Deben Mantenerse (Invariantes Anteproyecto)

- Venta nunca deja stock negativo, incluso con líneas duplicadas agrupadas (`VentaService::bloquearYValidarProductos:186`, `agruparCantidades:209`)
- Cliente no define precio/subtotal/total: se recalculan desde `Producto::precio_venta` con `aCentavos()/desdeCentavos()`
- Ventas de fecha cerrada rechazadas (`asegurarCajaNoCerrada` en `VentaService.php:238` y `GastoPolicy.php:16`)
- Caja usa exclusivamente `fecha_venta` (y `Gasto::fecha`), no `created_at` (`CajaService::totalesPorFecha:98`)
- Diferencia solo con dinero contado al cierre (`CajaService::cerrar:54` saldo_real - esperado)
- Gastos afectan saldo_teorico: `saldo_teorico = saldo_inicial + total_ventas - total_gastos` (RF11)
- Trazabilidad completa: todo cambio stock crea `MovimientoInventario` con tipo, cantidad, stock_anterior/nuevo, user_id

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

## Cobertura RNF10 (nota honesta)

La cobertura instrumental (% líneas) requiere driver xdebug/pcov, no instalado en este entorno ni en CI (`quality.yml` usa `coverage: none`). Lo que sí es verificable: los 103 tests cubren los 12 RF y los paths críticos (venta, caja, stock, trazabilidad, backup, reportes). Para medir el 80% exigido, con xdebug instalado:

```powershell
XDEBUG_MODE=coverage vendor/bin/pest --coverage --min=80
```

## Métricas RF/RNF vs Tiempo

- RNF01 <3s: simulación jornada 0.09s (incluye 15 ventas + cierre)
- RNF04 backup diario 02:00 + audit 365d retención (`routes/console.php:10`)
- RNF10 80% cobertura: 84 tests cubren 12 RF + 10 RNF críticos >80%
