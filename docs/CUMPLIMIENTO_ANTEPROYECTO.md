# Cumplimiento del Anteproyecto → Código MundoCoco

> Verificación punto por punto de `Anteproyecto - Mundo Coco.docx`, `RF y RNF.docx` y
> `Solución Sistema de Inventario y Facturación Mundo Coco.docx` contra el código.
> Cada fila indica dónde está implementado y qué evidencia técnica lo sustenta; algunas condiciones requieren comprobaciones adicionales.
> Las pruebas de cumplimiento están agrupadas en `tests/Feature/CumplimientoAnteproyectoTest.php`.
>
> Última revisión documental: 29/10/2026. Última suite local: **207 pruebas aprobadas (817 aserciones)**; la medición de cobertura crítica fue **91,3 %** en la línea base de 205 pruebas. Pint y build aprobados. Los 12 RF tienen evidencia de implementación y prueba automatizada. El cumplimiento integral de cada requisito permanece sujeto a la revisión de todos sus criterios y a la evidencia operacional pendiente. La validación en operación (piloto local, usabilidad, navegadores y métricas de infraestructura) sigue pendiente y se detalla en §7.

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
| **RF02** Inventario inicial | Producto, cantidad inicial, **fecha de registro** | `InventarioService::registrarInicial(…, $fecha)`; formulario de movimientos con fecha y hora; primer conteo físico en modo inicial (`registrarConteo(…, esInicial: true)`) | `RF05 cuenta solo desde el último inventario inicial`, `InventarioServiceTest`, `Cierre con conteo físico` (modo inicial) |
| **RF03** Adiciones/compras | **Producto(s)**, cantidad > 0, **fecha y hora**, tipo (compra/devolución/ajuste), observaciones; stock y historial automáticos | `InventarioService::registrarLote` (varios productos, una transacción); `CreateMovimiento` con repeater | `RF03 registra varios productos…`, `un lote con una línea inválida no deja nada a medias`, `el Administrador registra una compra de varios productos desde el panel`, `Cierre con conteo físico` (ajustes por sobrante/faltante) |
| **RF04** Ventas | Productos, cantidad, **precio (puede diferir)**, **fecha y hora**, método (Efectivo, Nequi); stock y total automáticos; sin stock insuficiente; alerta de mínimo | `VentaService::crear/actualizar` (único camino; el panel delega); `precio_base` + auditoría `venta_precio_modificado`; `DateTimePicker fecha_venta` (no futura); `productosBajoMinimo` + notificación | Bloque `RF04 precio distinto del base y fecha/hora de venta`, `VentasPanelTest`, `VentaServiceTest`, `VentaAlertaStockTest` |
| **RF05** Stock total | `Stock = Inicial + Entradas − Salidas` en tiempo real | `InventarioService::stockCalculado` (una consulta SQL, desde el último inicial según `fecha_movimiento`); columna "Stock calculado" en Productos (roja si no cuadra; una consulta por página) | `RF05 cuenta solo desde el último inventario inicial`, `RF05 detecta un stock registrado que no cuadra` |
| **RF06** Categorías | Helados, Bebidas, Aceites, Productos de Coco con sus productos; CRUD | `CategoriaSeeder`, `ProductoSeeder` (50 productos reales con las tarifas de la planilla de la tienda), `CategoriaResource` | `SimulacionRealMundoCocoTest`, `RF06 catálogo real de la planilla de la tienda` |
| **RF07** Alertas | Notificación visual en dashboard, **lista** de críticos, **sugerencias de reorden** | Widget `StockCritico` (tabla en el dashboard) + `StatsOverview`; `Producto::sugerenciaReorden()` (factor configurable) | `RF07: sugerencia de reorden…`, `la página de reportes y el dashboard con alertas se renderizan` |
| **RF08** Reportes de inventario | Por categoría, stock bajo, valorización, movimientos por período; **pantalla, PDF y Excel** | Página `Reportes` con secciones en pantalla para inventario por categoría (productos, stock, bajo mínimo y valorización), stock bajo, valorización global y movimientos; `ReporteExportService` (11 reportes × PDF/Excel/CSV), con descargas en cada sección | `ReporteExportTest` (recorre todos los tipos y formatos), `RF08: el inventario por categoría se muestra en pantalla con su valorización` |
| **RF09** Reportes de ventas | Diarias por producto, por categoría, más vendidos, ingresos por período, comparativo | `ReporteService::ventasDiariasPorProductoRango`, `ventasPorCategoria`, `productosMasVendidos`, `ingresosPorPeriodo`, `comparativoVentas` (excluyen ventas anuladas). Las dos primeras se descargan desde Otros reportes. El CSV independiente de ventas seleccionadas sí conserva las anuladas marcadas | `RF09: ventas diarias por producto…`, `ReportesServiceTest` |
| **RF10** Usuarios y roles | Administrador (total), **Operario** (ventas + consulta de inventario), **Consultor** (solo reportes); crear usuarios, asignar roles, **cambiar contraseñas** | `RoleSeeder` es la única fuente de permisos; todas las policies consultan permisos; `UserResource`; **perfil propio** (`->profile()`) | Bloque `RF10 roles estrictos…`, `cada usuario puede cambiar su contraseña desde su perfil` |
| **RF11** Caja diaria | Base, ventas en efectivo, ventas por Nequi, gastos; cuadre automático; **reporte de flujo diario**; `Saldo = Base + Efectivo + Nequi − Gastos` | `CajaService` (una caja por fecha y sucursal; totales calculados en servidor; gastos y retiros separados), `GastoResource` y `ReporteService::flujoCajaDiario`. La edición valida que las fechas original y de destino no tengan caja cerrada. | `CajasFlowTest`, `CajaConGastosTest` y `GastosPanelTest` (edición, recálculo, retiros y bloqueo de cajas cerradas). |
| **RF12** Trazabilidad | Fecha y hora exacta, usuario, tipo, cantidad, stock antes y después; búsqueda por producto, fecha, usuario y tipo | `movimientos_inventario` (`fecha_movimiento` + `created_at`), filtros en `MovimientosTable`; ventas, ediciones y anulaciones enlazan `referencia_type/id`; la anulación guarda causa (`config('mundococo.motivos_anulacion')`) + motivo libre; el descarte agrega una merma equivalente a la devolución. Los movimientos tienen detalle de solo lectura | `TrazabilidadTest`, `VentasPanelTest`, `MovimientosPanelTest`, bloque `Anulación de ventas` |

---

## 3. Requisitos no funcionales

| RNF | Exigido | Implementación | Prueba |
|---|---|---|---|
| **RNF01** Rendimiento | ≤ 3 s, 200 productos, 2.000 movimientos/mes, 5 usuarios | Índices operativos, agregados en SQL (valorización, ventas por categoría, stock calculado), paginación | `RendimientoTest` |
| **RNF02** Usabilidad | Español, intuitivo, responsivo, texto legible; manejo básico tras un máximo de dos horas de capacitación | Filament en español (`APP_LOCALE=es`), manual de usuario; capacitación presencial de aproximadamente 1,5 horas declarada por el usuario, sin medición cronometrada. **Parcial**. | Verificación de interfaz descrita en `docs/TESTING_REPORT.md`; capacitación declarada de aproximadamente 1,5 horas; sin registro cronometrado. |
| **RNF03** Compatibilidad | Chrome, Firefox, Safari y Edge; 1024×768 a 1920×1080; sin instalación | Chrome 154, Firefox 158 y Edge 156 superaron siete pantallas en cinco configuraciones. Edge también completó nueve pasos funcionales sobre MySQL aislado. En Safari se confirmó acceso al demo desde un iPhone, sin la batería completa. **Parcial por el alcance de Safari**. | 35 comprobaciones por navegador; flujos funcionales en Firefox y Edge; acceso a Safari comunicado por un autor. Edge: `evidencia_rnf/val_edge_resultados.json`. |
| **RNF04** Seguridad | Login, bcrypt, sesión 2 h, **auditoría de ventas y cambios de inventario**, backup diario | `User` (`hashed`), `SESSION_LIFETIME=120`, `AuditService` en ventas, ediciones, anulaciones, precios modificados, **todo cambio de stock**, cambios del catálogo (`producto_actualizado`), cierres de caja; pantalla **Auditoría** con resumen legible y detalle completo (solo lectura); `backup:database` diario; tokens de la API con caducidad (`SANCTUM_EXPIRATION`); exportes CSV/Excel sin fórmulas inyectables | `BackupDatabaseTest`, `RF03 … y audita`, `Pantallas del panel (humo)`, `RegresionAuditoriaTest` |
| **RNF05** Confiabilidad | ACID, backup 24 h, validación cliente y servidor, mensajes claros | `DB::transaction(…, 3)` + `lockForUpdate`; panel con `->databaseTransactions()`; validación en formularios y en servicios | `VentasPanelTest` (nada a medias), `VentaServiceTest` |
| **RNF06** Mantenibilidad | Documentación, patrones, **parámetros configurables**, logs | Servicios + policies + observers; `config/mundococo.php` (precios, reorden, contacto de datos); `docs/MANUAL_TECNICO.md` | `RF07: … factor configurable`, `rechaza un precio fuera del rango configurado` |
| **RNF07** Escalabilidad | Crecimiento, nuevos módulos, **múltiples sucursales**, **integración externa** | Modelo `Sucursal` (ventas, cajas, gastos y movimientos ligados a una sucursal; hoy la principal); **API REST v1** de solo lectura con tokens Sanctum (`app/Http/Controllers/Api/V1/`) | Bloque `RNF07 escalabilidad…` |
| **RNF08** Portabilidad | MySQL, PostgreSQL o SQL Server | MySQL 8.4.3 es el único motor comprobado para operación; SQLite en memoria se usa solo en pruebas automatizadas. Las variantes de SQL para otros motores no equivalen a verificarlas. **Parcial**. | Operación y concurrencia medidas en MySQL; suite automatizada aislada en SQLite. Sin pruebas en PostgreSQL ni SQL Server. |
| **RNF09** Eficiencia | < 2 s de carga, BD inicial < 100 MB | Assets compilados con Vite; consultas agregadas; API de ventas paginada (máx. 200 por página) y *Reportes* con movimientos limitados | `npm run build`, `la API de ventas pagina y acota por_pagina` |
| **RNF10** Cumplimiento | Ley de datos, W3C, patrones, **≥ 80 % de cobertura en funciones críticas** | Consentimiento Ley 1581 (ver §5); `phpunit.cobertura.xml` mide servicios, dinero, observers, policies, middleware y modelos; CI exige `--min=80`. Sin evaluación formal completa de estándares web (HTML/CSS/accesibilidad). **Parcial**. | Cobertura local **91,3 %** (29/10/2026); el 92,5 % de 22/10/2026 es histórico. El CI sigue bloqueado por facturación. |

---

## 4. Anteproyecto: objetivos, resultados y contexto

| Punto | Implementación | Prueba |
|---|---|---|
| OG / OE3: control de stock, ventas y cuadre de caja con métodos de pago | Módulos Productos, Movimientos, Ventas, Cajas y Gastos integrados | `SimulacionRealMundoCocoTest` |
| OE4: validar midiendo **precisión de inventario, tiempo de proceso y reducción de pérdidas** | Indicadores de gestión exportables; el piloto con una participante y sin línea base comparable no permitió cuantificar esos efectos. **Parcialmente cumplido**. | `OE4: indicadores de pérdidas, agotados y cuadre de caja` verifica cálculos; `RendimientoTest` mide operaciones técnicas. Ninguna de esas pruebas mide la mejora real de la tienda. |
| Contexto: "descuentos aplicados o las devoluciones registradas" en el cuadre | Descuentos = precio distinto del base (auditado); devoluciones = **anulación de venta** (repone stock, sale de caja, queda auditada con causa y motivo; si la mercancía se desecha registra además una **merma**, para no inflar el stock) | Bloque `Anulación de ventas` |
| Contexto: registro formal de gastos de materia prima | `GastoResource` (categorías materia prima, servicios, transporte, otros), resta en la caja | `CajaConGastosTest` |
| Resultados: alertas en tiempo real, reportes automatizados | RF07 + RF08/RF09 (ver §2) | — |
| Justificación: respaldos digitales | `backup:database` diario con retención de 30 días | `BackupDatabaseTest` |

---

## 5. Marco legal

| Norma | Implementación |
|---|---|
| Ley 1581 de 2012 / Decreto 1377 de 2013 | Política pública en `/privacidad`, enlazada desde el login; **consentimiento informado obligatorio** antes de operar (`ExigirConsentimientoDatos` + página `ConsentimientoDatos`), con fecha (`users.politica_aceptada_at`) y registro en auditoría; canal de habeas data configurable (`CONTACTO_DATOS`). Pruebas: bloque `Ley 1581…`. |
| Ley 527 de 1999 | Reportes y registros electrónicos exportables (PDF/Excel) con fecha de generación. |
| Ley 23 de 1982 / Ley 603 de 2000 | Dependencias con licencia MIT (Laravel, Filament, Sanctum, DomPDF, PhpSpreadsheet), declaradas en `composer.json`. |

---

## 6. Qué queda a cargo del equipo

- **Medir la cobertura (RNF10):** línea base del 29/10/2026 con pcov: **91,3 %** (205 pruebas, 803 aserciones). El 92,5 % de 22/10/2026 queda como referencia histórica. Los controladores no están en el alcance de `phpunit.cobertura.xml`. El CI (GitHub Actions con pcov) aún no se ha podido ejecutar por un bloqueo de facturación de la cuenta. Para repetir la medición: `composer test:cobertura`; para la verificación completa: `vendor/bin/pint --test`, `composer test`, `npm run build`.
- **Actualizar una base existente:** `php artisan migrate` (si hay cajas duplicadas de una misma fecha, la migración del índice único se detiene y las lista: corríjalas primero), luego `php artisan db:seed --class=MetodoPagoSeeder` (agrega Nequi) y `php artisan db:seed --class=RoleSeeder` (permisos estrictos de RF10). Los usuarios aceptarán la política de datos en su próximo ingreso.
- **Validación con usuarios reales (OE4):** se realizó un piloto remoto los días 20 y 21/10/2026 con una administradora. Hay una respuesta de encuesta y confirmación por correo de recepción del material, uso del sistema y cierre de caja declarado en 15–20 minutos. Los soportes se encuentran en `05 Validación en tienda` y sus copias aptas para entrega en `07 Paquete de Entrega`. No hay medición objetiva anterior comparable ni muestra suficiente para cuantificar mejora de precisión, pérdidas o tiempo; OE4 permanece parcialmente cumplido.

---

## 7. Línea base y estado de verificación (29/10/2026)

Verificación ejecutada en local (Windows, Laragon, PHP 8.4, SQLite en memoria para pruebas):

| Comando | Resultado |
|---|---|
| `php artisan test` | 207 pruebas aprobadas, 817 aserciones |
| `composer test:cobertura` | 91,3 % en el alcance de `phpunit.cobertura.xml` |
| `vendor/bin/pint --test` | Sin errores de estilo |
| `npm run build` | Compilación correcta (Vite 8) |

Estados usados: **Implementado** = existe en código; **Probado** = tiene prueba automatizada que se ejecuta en la suite; **Validado en operación** = evidencia en un entorno real con usuarios o infraestructura representativa.

| Requisito | Implementado | Probado | Validado en operación |
|---|---|---|---|
| RF01–RF07, RF09–RF12 | Sí | Sí | Pendiente del piloto local |
| RF08 | Sí, incluida la vista de inventario por categoría en pantalla | Sí (`ReporteExportTest`, prueba de pantalla RF08) | Pendiente del piloto local |
| RNF01 Rendimiento | Sí | Parcial (`RendimientoTest` técnico) | Pendiente: medición con 200 productos, 2.000 movimientos/mes y 5 usuarios |
| RNF02 Usabilidad | Sí (español, responsivo, manual) | No | Capacitación declarada de 1,5 h; pendientes comprobación de tareas y accesibilidad |
| RNF03 Compatibilidad | Sí | Parcial (Chrome, Firefox, Edge; Safari solo acceso) | Pendiente: flujo completo en Safari y matriz de resoluciones |
| RNF04 Seguridad | Sí | Sí (sesión, bcrypt, auditoría, backup) | Pendiente: comprobar respaldo programado en operación |
| RNF05 Confiabilidad | Sí | Sí (ACID, validaciones, backup) | Parcial: restauración y disponibilidad sin medir en operación |
| RNF06 Mantenibilidad | Sí | Sí (configuración y logs) | Pendiente: comprobar actualización sin pérdida de datos |
| RNF07 Escalabilidad | Sí (sucursales y API) | Sí | Pendiente de uso real |
| RNF08 Portabilidad | Solo MySQL comprobado; SQLite en pruebas | Suite en SQLite y operación en MySQL | PostgreSQL y SQL Server no verificados: **requiere decisión** (probarlos o declararlos fuera de alcance) |
| RNF09 Eficiencia | Sí (assets, paginación, límites) | Parcial (build y página de reportes) | Pendiente: memoria ≤ 512 MB/usuario, 1 Mbps y < 2 s por página |
| RNF10 Cumplimiento | Sí (Ley 1581, cobertura) | Sí (cobertura 91,3 %) | Pendiente: revisión formal W3C/accesibilidad |

### Pendientes para cerrar la entrega

| # | Actividad | Responsable | Criterio de aceptación |
|---|---|---|---|
| 1 | Evaluación de usabilidad y accesibilidad (RNF02) | Equipo + usuarios de la tienda | Tareas básicas completadas tras ≤ 2 h de capacitación, sin barreras críticas; informe guardado |
| 2 | Matriz de navegadores (RNF03) | Equipo de pruebas | Flujo completo en Chrome, Firefox, Edge y Safari; 1024×768 a 1920×1080; evidencia por navegador y versión |
| 3 | Decisión y prueba de motores (RNF08) | Líder técnico | MySQL declarado operativo; PostgreSQL/SQL Server probados o marcados explícitamente fuera de alcance |
| 4 | Revisión de estándares web (RNF10) | Equipo de desarrollo | Informe HTML/CSS/accesibilidad de pantallas principales y correcciones aplicadas |
| 5 | Medición de operación (RNF01, RNF04, RNF05, RNF09) | Líder técnico + infraestructura | Resultados reproducibles con entorno documentado: ≤ 3 s, memoria, 1 Mbps, backup/restauración y disponibilidad |
| 6 | Piloto local y cierre (OE4) | Equipo + tienda | Jornada completa validada; incidencias críticas resueltas; manuales y esta matriz actualizados |


## 8. Lista de aceptación por requisito

Esta lista sigue `requerimientos.md`. Los responsables son roles propuestos, no personas designadas. La evidencia de código y pruebas se detalla en §§2–3; su existencia no demuestra por sí sola cada criterio ni sustituye la validación operativa. La revisión detallada se hará en el orden del plan y las brechas se registrarán al comprobarlas.

| Requisito | Criterio de aceptación resumido | Responsable propuesto | Evidencia y estado pendiente |
|---|---|---|---|
| RF01 | CRUD de productos, código único, datos y permisos exigidos | Desarrollo + tienda | §2; confirmar flujo completo en piloto |
| RF02 | Registrar producto, cantidad y fecha del inventario inicial | Desarrollo + tienda | §2; confirmar flujo en piloto |
| RF03 | Registrar entradas positivas de uno o varios productos, fecha, tipo y observaciones | Desarrollo + tienda | §2; confirmar flujo en piloto |
| RF04 | Vender con precio, fecha y pago válidos; actualizar stock y alertar mínimo | Desarrollo + tienda | §2; confirmar flujo y alertas en piloto |
| RF05 | Calcular stock a partir del inventario inicial y movimientos | Desarrollo + tienda | §2; contrastar saldos en piloto |
| RF06 | CRUD de categorías y catálogo requerido | Desarrollo + tienda | §2; confirmar catálogo y operación con tienda |
| RF07 | Mostrar productos críticos y sugerencias de reorden | Desarrollo + tienda | §2; confirmar utilidad en piloto |
| RF08 | Inventario por categoría, stock bajo, valorización y movimientos; pantalla, PDF y Excel | Desarrollo + pruebas | §2; revisar resultados, filtros, permisos y consistencia entre formatos en el punto 2 |
| RF09 | Reportes de ventas por producto, categoría, período y comparación | Desarrollo + tienda | §2; verificar resultados y uso en piloto |
| RF10 | Roles, permisos, usuarios y contraseñas según requisito | Desarrollo + tienda | §2; validar con usuarios y permisos reales |
| RF11 | Apertura, ventas, gastos, cuadre y flujo diario | Desarrollo + tienda | §2; contrastar fórmula y jornada real |
| RF12 | Historial con fecha, hora, usuario, tipo, cantidad, saldos y filtros | Desarrollo + tienda | §2; revisar búsqueda y trazabilidad en piloto |
| RNF01 | ≤ 3 s, 200 productos, 2.000 movimientos/mes, 5 usuarios y disponibilidad del 99 % en horario comercial | Desarrollo + infraestructura | §3 y §7; alcance de medición y operación pendientes de acuerdo y medición |
| RNF02 | Español, usabilidad con ≤ 2 h de capacitación, adaptación y texto mínimo de 12 pt con contraste adecuado | Desarrollo + usuarios | §3; evaluación guiada y accesibilidad pendientes |
| RNF03 | Navegadores y versiones, sistemas operativos y resoluciones indicados en `requerimientos.md` | Pruebas | §3; falta matriz completa de flujos y Safari |
| RNF04 | Autenticación, bcrypt, sesión de 2 h, auditoría y respaldo diario | Desarrollo + infraestructura | §3; verificar programación y respaldo en operación |
| RNF05 | Transacciones, respaldo cada 24 h, validaciones, errores y disponibilidad del 99 % | Desarrollo + infraestructura | §3; restauración y disponibilidad operativas pendientes |
| RNF06 | Documentación, estructura, parámetros, logs y actualización sin pérdida de datos | Desarrollo + infraestructura | §3; verificar actualización y recuperación con datos representativos |
| RNF07 | Crecimiento del 50 % anual, extensibilidad, preparación para sucursales e integración | Desarrollo + infraestructura | §3; capacidad de crecimiento y alcance de sucursales requieren validación específica |
| RNF08 | Plataforma, motor de base de datos y servidor web compatibles según requisito | Líder técnico | §3; decidir y probar alcance de motores y servidores web; registrar exclusiones acordadas |
| RNF09 | ≤ 512 MB por usuario concurrente, BD inicial < 100 MB, conexión de 1 Mbps y páginas principales < 2 s | Desarrollo + infraestructura | §3; condiciones de medición y mediciones operativas pendientes |
| RNF10 | Protección de datos, HTML5/CSS3, patrones y cobertura crítica ≥ 80 % | Desarrollo + responsable de datos | §§3 y 5; cobertura en §7; revisión formal web y validación legal pendientes |

Los tiempos de RNF01 y RNF09 son criterios distintos: el primero abarca operaciones y el segundo la carga de páginas principales. Se deben acordar sus puntos de inicio y fin, distribución de mediciones y condiciones antes de declarar cumplimiento. La disponibilidad, la memoria y el ancho de banda se medirán en el entorno y período acordados; aún no hay aceptación de esas condiciones.


### Acuerdos y decisiones pendientes de medición

- **Entorno objetivo:** instalación local en el equipo de la tienda, según lo indicado por el usuario. Equipo informado: portátil con Intel Core i5, 12 GB de RAM, disco de 500 GB y Windows 11. Modelo y generación del procesador, almacenamiento disponible y configuración efectiva quedan pendientes de comprobar en el equipo.
- **RNF01, tiempo de respuesta:** se acuerda medir desde la acción del usuario hasta que aparezca el resultado, incluyendo consultas, guardados y reportes. Descargas y procesos programados se registrarán por separado; su tratamiento frente a la expresión «cualquier operación» debe quedar explícito en los resultados y no se presumirá una excepción al requisito sin acordarla.
- **Horario de la tienda:** lunes a domingo de 9:30 a. m. a 7:00 p. m., según el usuario. El usuario autorizó el 29/10/2026 registrar este horario para el requisito; el horario original de 8:00 a. m. a 8:00 p. m. se conserva como antecedente en `requerimientos.md`.
- **Disponibilidad:** el usuario autorizó supervisar durante dos días consecutivos en horario comercial. Son 19 horas programadas; el 99 % admite como máximo 11 minutos y 24 segundos acumulados de indisponibilidad en ese período. Falta concretar el método de supervisión y el tratamiento de paradas planificadas. Dos días solo permiten concluir sobre el período observado, no demostrar disponibilidad sostenida a largo plazo. Se calculará con registros del entorno instalado, sin extrapolar una observación corta a un período mayor.
- **RNF09:** el punto de medición y las condiciones para memoria, conexión de 1 Mbps, tamaño inicial y carga de páginas principales se concretarán en el protocolo; los límites originales siguen vigentes.


### Seguimiento de RF08

La pantalla y la exportación de inventario por categoría usan la misma consulta de inventario actual, independientemente del período; los movimientos sí se filtran por período. Los importes en pantalla conservan dos decimales. Los PDF de inventario actual, stock bajo y valorización se identifican como estado actual, no como inventario histórico del período seleccionado.

Verificación posterior a la línea base: Pint y build aprobados; `composer test` aprobó 206 pruebas (810 aserciones). Se comprobó con datos controlados que la pantalla, el CSV y el Excel conservan las cantidades y centavos del inventario; las pruebas existentes comprueban generación PDF/Excel y permisos de exportación. Estas comprobaciones se ampliaron posteriormente, como se indica en la actualización al final de esta sección; permanece pendiente la validación operativa. La cobertura de 91,3 % de §7 corresponde a la medición anterior, no a una nueva medición posterior a estos cambios.


### Evidencia aportada del piloto de octubre

Fuente: PDF de la cadena de correo `Elementos enviados_ DIAZ RAMIREZ CRISTIAN CAMILO - Outlook.pdf`, aportado por el usuario, páginas 1–2. La participante informó el 22/10/2026 que utilizó el sistema el 20 y el 21 de octubre, que el cierre de caja le tomó entre 15 y 20 minutos y que no tuvo problemas durante esos dos días. También confirmó la recepción del material y haber visto el video. El mensaje del equipo proponía conteo, ventas, gastos y cierre, pero la respuesta no identifica individualmente cuáles de esas tareas realizó ni documenta la duración de la capacitación.

El correo proporciona acceso al demo en la nube; por tanto, acredita experiencia declarada en ese piloto, no la instalación local prevista en el equipo de la tienda. La ausencia de problemas comunicados no equivale a supervisión continua: no hay registros de disponibilidad, duración de caídas ni cobertura del horario completo. Esta evidencia no demuestra por sí sola el 99 % de disponibilidad ni sustituye la verificación del entorno local. No se requiere repetir automáticamente el piloto ya realizado; se complementará solo la evidencia que falte.


### Encuesta de satisfacción aportada

Fuente: `MundoCoco - Encuesta de satisfacción del usuario final - Formularios de Google.pdf`, cuatro páginas, aportado por el usuario y revisado visualmente. Presenta una respuesta anónima con rol Administrador: facilidad de registrar ventas **5/5**, apertura y cierre de caja **5/5**, utilidad de alertas **5/5**, claridad de reportes **5/5** y comprensión de mensajes de error **4/5**. La persona respondió que el inventario se descuenta correctamente, percibió el cuadre más rápido que el proceso anterior, indicó ningún problema en entradas o salidas y calificó la experiencia como muy buena. Solicitó poder imprimir facturas.

Esta respuesta es evidencia subjetiva favorable de experiencia de uso para RF04, RF05, RF07, RF08/RF09, RF11 y RNF02; no prueba individualmente todos sus criterios, no cuantifica una mejora ni permite generalizar con una sola respuesta. La encuesta es anónima: no se atribuye su respuesta a una persona identificable sin evidencia adicional. No documenta la duración de la capacitación ni una evaluación de contraste, teclado o dispositivos. La impresión de facturas se registra como mejora para una versión futura, por decisión del usuario; no forma parte del alcance de esta entrega y no se presenta como implementada.


Actualización posterior: se generó un PDF con datos controlados y se extrajo su texto; se comprobó la etiqueta «Estado actual», la categoría, el stock y los valores 10002.50 y 20005.00. La prueba automatizada de movimientos verifica el mismo período en pantalla, CSV y Excel. La suite completa aprobó 207 pruebas y 817 aserciones, además de Pint y build. Se generó también un PDF de movimientos con datos dentro y fuera del período y se comprobó por extracción de texto que solo aparece el movimiento incluido. Queda pendiente validar la versión actual en el entorno local de la tienda. El 91,3 % sigue siendo la medición de cobertura anterior.


### Capacitación presencial informada

El usuario indicó que la capacitación presencial duró aproximadamente una hora y media y se realizó un día antes de enviar el correo. Es un tiempo declarado, no una medición cronometrada ni evidencia independiente de que todas las tareas básicas se completaron después de la capacitación. Está dentro del límite de dos horas de RNF02; queda pendiente precisar a qué correo se refiere la fecha y completar la comprobación de accesibilidad y tareas.


En la revisión inicial de RNF02 se encontraron tamaños inferiores a 12 pt en plantillas propias. Se ajustaron los textos de Reportes y sus enlaces de descarga a 16 px (12 pt CSS), y se asociaron las etiquetas «Desde» y «Hasta» con sus campos. La suite y el build aprobaron; falta revisar visualmente y con teclado esta pantalla, así como otras plantillas y los componentes del panel. No se declara el cumplimiento general de tamaño o contraste sin esa revisión.
