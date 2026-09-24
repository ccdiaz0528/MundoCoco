# Cumplimiento del Anteproyecto → Código MundoCoco

> Verificación punto por punto de `DOCS DE PROYECTO/Anteproyecto - Mundo Coco.docx`, `RF y RNF.docx` y
> `Solución Sistema de Inventario y Facturación Mundo Coco.docx` contra el código.
> Cada fila cita dónde está implementado y **qué prueba automática lo demuestra**.
> Las pruebas de cumplimiento están agrupadas en `tests/Feature/CumplimientoAnteproyectoTest.php`.
>
> Última revisión: 24/09/2026 — 0 desviaciones declaradas.

---

## 1. Decisiones donde los documentos se contradecían

Los documentos del proyecto no coinciden entre sí en tres puntos. Se resolvieron así, cumpliendo **ambas** fuentes:

| Punto | RF y RNF.docx | Anteproyecto | Implementación |
|---|---|---|---|
| Métodos de pago | "Efectivo, Nequi" (RF04, RF11) | "efectivo, transferencias bancarias, tarjetas" (formulación) | Los cuatro: **Efectivo, Nequi, Transferencia, Tarjeta**. La caja muestra Nequi por separado (RF11). |
| Precio de venta | "puede diferir del precio base" (RF04) | Reducir errores y pérdidas | Editable por línea, **validado en servidor** dentro del rango de RF01 ($2.000–$95.000, configurable), guardando el precio base y **auditando** cada diferencia. |
| Nombre del rol | "Operario" (RF10) | — | Rol `Operario` (las instalaciones previas con "Operador" se renombran automáticamente). |

---

## 2. Requisitos funcionales (RF y RNF.docx)

| RF | Exigido | Implementación | Prueba |
|---|---|---|---|
| **RF01** Productos | Crear, consultar, actualizar, **eliminar**; categoría; precio $2.000–$95.000; stock mínimo; descripción; código único; solo Administrador | `ProductoResource` + `ProductoForm` (rango desde `config/mundococo.php`); `Producto::booted()` genera `codigo`; eliminar = borrado lógico (`SoftDeletes`) con restaurar, sin romper ventas ni trazabilidad; `ProductoPolicy` por permisos | `RF01 eliminar productos (borrado lógico)`, `ProductoCodigoTest`, `RF10 … Operario … nada más` |
| **RF02** Inventario inicial | Producto, cantidad inicial, **fecha de registro** | `InventarioService::registrarInicial(…, $fecha)`; formulario de movimientos con fecha y hora | `RF05 cuenta solo desde el último inventario inicial`, `InventarioServiceTest` |
| **RF03** Adiciones/compras | **Producto(s)**, cantidad > 0, **fecha y hora**, tipo (compra/devolución/ajuste), observaciones; stock y historial automáticos | `InventarioService::registrarLote` (varios productos, una transacción); `CreateMovimiento` con repeater | `RF03 registra varios productos…`, `un lote con una línea inválida no deja nada a medias`, `el Administrador registra una compra de varios productos desde el panel` |
| **RF04** Ventas | Productos, cantidad, **precio (puede diferir)**, **fecha y hora**, método (Efectivo, Nequi); stock y total automáticos; sin stock insuficiente; alerta de mínimo | `VentaService::crear/actualizar` (único camino; el panel delega); `precio_base` + auditoría `venta_precio_modificado`; `DateTimePicker fecha_venta` (no futura); `productosBajoMinimo` + notificación | Bloque `RF04 precio distinto del base y fecha/hora de venta`, `VentasPanelTest`, `VentaServiceTest`, `VentaAlertaStockTest` |
| **RF05** Stock total | `Stock = Inicial + Entradas − Salidas` en tiempo real | `InventarioService::stockCalculado` (una consulta SQL, desde el último inicial); columna "Stock calculado" en Productos (roja si no cuadra) | `RF05 cuenta solo desde el último inventario inicial`, `RF05 detecta un stock registrado que no cuadra` |
| **RF06** Categorías | Helados, Bebidas, Aceites, Productos de Coco con sus productos; CRUD | `CategoriaSeeder`, `ProductoSeeder` (42 productos del documento), `CategoriaResource` | `SimulacionRealMundoCocoTest` |
| **RF07** Alertas | Notificación visual en dashboard, **lista** de críticos, **sugerencias de reorden** | Widget `StockCritico` (tabla en el dashboard) + `StatsOverview`; `Producto::sugerenciaReorden()` (factor configurable) | `RF07: sugerencia de reorden…`, `la página de reportes y el dashboard con alertas se renderizan` |
| **RF08** Reportes de inventario | Por categoría, stock bajo, valorización, movimientos por período; **pantalla, PDF y Excel** | Página `Reportes` + `ReporteExportService` (11 reportes × PDF/Excel/CSV, botón en cada sección) | `ReporteExportTest` (recorre todos los tipos y formatos) |
| **RF09** Reportes de ventas | Diarias por producto, por categoría, más vendidos, ingresos por período, comparativo | `ReporteService::ventasDiariasPorProductoRango`, `ventasPorCategoria`, `productosMasVendidos`, `ingresosPorPeriodo`, `comparativoVentas` (todas excluyen ventas anuladas) | `RF09: ventas diarias por producto…`, `ReportesServiceTest` |
| **RF10** Usuarios y roles | Administrador (total), **Operario** (ventas + consulta de inventario), **Consultor** (solo reportes); crear usuarios, asignar roles, **cambiar contraseñas** | `RoleSeeder` es la única fuente de permisos; todas las policies consultan permisos; `UserResource`; **perfil propio** (`->profile()`) | Bloque `RF10 roles estrictos…`, `cada usuario puede cambiar su contraseña desde su perfil` |
| **RF11** Caja diaria | Base, ventas en efectivo, ventas por Nequi, gastos; cuadre automático; **reporte de flujo diario**; `Saldo = Base + Efectivo + Nequi − Gastos` | `CajaService` (columna `total_nequi`), `GastoResource`, `ReporteService::flujoCajaDiario` (con base de caja) + exportación `flujo_caja` | `la caja separa Nequi y lo suma al saldo…`, `RF11: flujo diario…`, `CajasFlowTest`, `CajaConGastosTest` |
| **RF12** Trazabilidad | Fecha y hora exacta, usuario, tipo, cantidad, stock antes y después; búsqueda por producto, fecha, usuario y tipo | `movimientos_inventario` (`fecha_movimiento` + `created_at`), filtros en `MovimientosTable`; ventas, ediciones y anulaciones enlazan `referencia_type/id` | `TrazabilidadTest`, `VentasPanelTest`, bloque `Anulación de ventas` |

---

## 3. Requisitos no funcionales

| RNF | Exigido | Implementación | Prueba |
|---|---|---|---|
| **RNF01** Rendimiento | ≤ 3 s, 200 productos, 2.000 movimientos/mes, 5 usuarios | Índices operativos, agregados en SQL (valorización, ventas por categoría, stock calculado), paginación | `RendimientoTest` |
| **RNF02** Usabilidad | Español, intuitivo, responsivo, texto legible | Filament en español (`APP_LOCALE=es`), manual de usuario | `docs/MANUAL_USUARIO.md` |
| **RNF03** Compatibilidad | Navegadores modernos, 1024–1920 px, sin instalación | Aplicación web (Filament + Tailwind) | `npm run build` en CI |
| **RNF04** Seguridad | Login, bcrypt, sesión 2 h, **auditoría de ventas y cambios de inventario**, backup diario | `User` (`hashed`), `SESSION_LIFETIME=120`, `AuditService` en ventas, ediciones, anulaciones, precios modificados, **todo cambio de stock**, cierres de caja; pantalla **Auditoría** (solo lectura); `backup:database` diario | `BackupDatabaseTest`, `RF03 … y audita`, `Pantallas del panel (humo)` |
| **RNF05** Confiabilidad | ACID, backup 24 h, validación cliente y servidor, mensajes claros | `DB::transaction(…, 3)` + `lockForUpdate`; panel con `->databaseTransactions()`; validación en formularios y en servicios | `VentasPanelTest` (nada a medias), `VentaServiceTest` |
| **RNF06** Mantenibilidad | Documentación, patrones, **parámetros configurables**, logs | Servicios + policies + observers; `config/mundococo.php` (precios, reorden, contacto de datos); `docs/MANUAL_TECNICO.md` | `RF07: … factor configurable`, `rechaza un precio fuera del rango configurado` |
| **RNF07** Escalabilidad | Crecimiento, nuevos módulos, **múltiples sucursales**, **integración externa** | Modelo `Sucursal` (ventas, cajas, gastos y movimientos ligados a una sucursal; hoy la principal); **API REST v1** de solo lectura con tokens Sanctum | Bloque `RNF07 escalabilidad…` |
| **RNF08** Portabilidad | MySQL, PostgreSQL o SQL Server | SQL propio con variantes por motor (`ReporteService::expresionFecha`, `GROUP BY` por expresión); respaldo portable | Suite completa (SQLite) + desarrollo en MySQL |
| **RNF09** Eficiencia | < 2 s de carga, BD inicial < 100 MB | Assets compilados con Vite; consultas agregadas | `npm run build` |
| **RNF10** Cumplimiento | Ley de datos, W3C, patrones, **≥ 80 % de cobertura en funciones críticas** | Consentimiento Ley 1581 (ver §5); `phpunit.cobertura.xml` mide servicios, dinero, observers, policies, middleware y modelos; CI exige `--min=80` | Paso "Coverage of critical code (RNF10)" en `.github/workflows/quality.yml` |

---

## 4. Anteproyecto: objetivos, resultados y contexto

| Punto | Implementación | Prueba |
|---|---|---|
| OG / OE3: control de stock, ventas y cuadre de caja con métodos de pago | Módulos Productos, Movimientos, Ventas, Cajas y Gastos integrados | `SimulacionRealMundoCocoTest` |
| OE4: validar midiendo **precisión de inventario, tiempo de proceso y reducción de pérdidas** | Sección **Indicadores de gestión** (precisión RF05, pérdidas por mermas/ajustes valorizadas, productos agotados, cajas con diferencia, faltantes/sobrantes, anulaciones), exportable | `OE4: indicadores de pérdidas, agotados y cuadre de caja`, `RendimientoTest` (tiempo) |
| Contexto: "descuentos aplicados o las devoluciones registradas" en el cuadre | Descuentos = precio distinto del base (auditado); devoluciones = **anulación de venta** (repone stock, sale de caja, queda auditada) | Bloque `Anulación de ventas` |
| Contexto: registro formal de gastos de materia prima | `GastoResource` (categorías materia prima, servicios, transporte, otros), resta en la caja | `CajaConGastosTest` |
| Resultados: alertas en tiempo real, reportes automatizados | RF07 + RF08/RF09 (ver §2) | — |
| Justificación: respaldos digitales | `backup:database` diario con retención de 30 días | `BackupDatabaseTest` |

---

## 5. Marco legal

| Norma | Implementación |
|---|---|
| Ley 1581 de 2012 / Decreto 1377 de 2013 | Política pública en `/privacidad`, enlazada desde el login; **consentimiento informado obligatorio** antes de operar (`ExigirConsentimientoDatos` + página `ConsentimientoDatos`), con fecha (`users.politica_aceptada_at`) y registro en auditoría; canal de habeas data configurable (`CONTACTO_DATOS`). Pruebas: bloque `Ley 1581…`. |
| Ley 1266 de 2008 | Registros financieros (ventas, gastos, caja) solo visibles según rol; auditoría de operaciones. |
| Ley 527 de 1999 | Reportes y registros electrónicos exportables (PDF/Excel) con fecha de generación. |
| Ley 23 de 1982 / Ley 603 de 2000 | Dependencias con licencia MIT (Laravel, Filament, Sanctum, DomPDF, PhpSpreadsheet), declaradas en `composer.json`. |

---

## 6. Qué queda a cargo del equipo

- **Medir la cobertura (RNF10):** el entorno local no tiene xdebug ni pcov. La medición corre en CI (GitHub Actions con pcov). Localmente: instalar pcov o xdebug y ejecutar `composer test:cobertura`.
- **Actualizar una base existente:** `php artisan migrate`, luego `php artisan db:seed --class=MetodoPagoSeeder` (agrega Nequi) y `php artisan db:seed --class=RoleSeeder` (permisos estrictos de RF10). Los usuarios aceptarán la política de datos en su próximo ingreso.
- **Validación con usuarios reales (OE4):** las pruebas de usabilidad con el personal de MundoCoco y la medición de indicadores en operación real son actividades del proyecto; el sistema provee los indicadores para registrarlas.
