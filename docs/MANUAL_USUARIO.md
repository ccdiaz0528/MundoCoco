# Manual de Usuario — Sistema de Gestión MundoCoco

> Aplicación web para el control de inventario, ventas y caja de la tienda MundoCoco.
> Panel de administración: `http://tu-servidor/admin` · Interfaz completamente en español.

---

## 1. Ingreso al sistema

1. Abre el navegador (Chrome, Edge, Firefox o Safari) y entra a `/admin`.
2. Escribe tu **correo electrónico** y tu **contraseña**.
3. Pulsa **Iniciar sesión**.

- **Primer ingreso:** el sistema te muestra la autorización para el tratamiento de tus datos personales (Ley 1581 de 2012). Léela y pulsa **He leído y autorizo el tratamiento de mis datos**; sin esa autorización no puedes operar. La política completa está en `/privacidad` y enlazada desde la pantalla de ingreso.
- **Cambiar tu contraseña:** menú de tu usuario (arriba a la derecha) → **Perfil**.
- Si olvidas tu contraseña, pide al Administrador que te asigne una nueva desde **Seguridad → Usuarios**.
- La sesión se cierra sola después de 2 horas sin actividad.

### 1.1 Roles de usuario

| Rol | Qué puede hacer |
|-----|-----------------|
| **Administrador** | Todo: catálogo, entradas de inventario y conteo físico, ventas y anulaciones, cajas, gastos y retiros, informes, auditoría, usuarios y roles. |
| **Operario** | Registrar y corregir ventas, y consultar el inventario (productos, categorías y movimientos). |
| **Consultor** | Solo ver y descargar los reportes. |

---

## 2. Recorrido diario recomendado

| Paso | Quién | Dónde |
|---|---|---|
| 1. Abrir la caja con el dinero base | Administrador | *Operación diaria → Cajas → Nueva caja* |
| 2. Registrar entradas de mercancía | Administrador | *Catálogo → Movimientos → Nuevo movimiento* (sección 6.4) |
| 3. Registrar cada venta | Operario o Administrador | *Operación diaria → Ventas → Nueva venta* (sección 3) |
| 4. Registrar los gastos y las consignaciones del día | Administrador | *Operación diaria → Gastos → Nuevo gasto* (sección 5) |
| 5. Revisar alertas e informes | Todos según su rol | *Escritorio* e *Informes → Reportes* (sección 8) |
| 6. Contar el inventario y registrar el conteo | Administrador | *Catálogo → Conteo físico* (sección 6.5) |
| 7. Cerrar la caja contando el dinero | Administrador | *Cajas → Cerrar caja* (sección 4) |

> Antes del primer día de uso, el Administrador registra el **inventario inicial** con un conteo físico (sección 6.5). Hasta entonces los productos tienen stock 0 y aparecen en stock bajo.

> Regla de oro: **una fecha con caja cerrada queda bloqueada**. Ya no se pueden registrar, editar ni anular ventas ni gastos de ese día. Revisa bien antes de cerrar.

---

## 3. Ventas

*Menú: Operación diaria → Ventas.*

Cada fila muestra número de venta, **método de pago** (Efectivo, Nequi, Transferencia o Tarjeta), total, **estado** (Vigente o Anulada), observaciones y fecha. Filtra por método de pago o por anuladas.

### 3.1 Registrar una venta (Nueva venta)

1. Elige el **método de pago**.
2. Revisa la **fecha y hora de venta**: por defecto es el momento actual; puedes corregirla (por ejemplo, si registras una venta del mediodía más tarde), pero no puede ser futura.
3. Pulsa **+ Agregar producto**, busca el producto por nombre o código e indica la **cantidad**.
4. El sistema propone el **precio base** del producto. Si aplicas un descuento o recargo, cámbialo: debe quedar entre **$2.000 y $95.000**. El subtotal y el total se calculan solos.
5. Guarda. El stock se descuenta de inmediato y la venta se suma a la caja de su fecha. Si algún producto queda en su stock mínimo o por debajo, aparece un aviso.

Reglas importantes:
- No se puede vender más de lo que hay en stock.
- No se pueden vender productos inactivos ni eliminados.
- Si la caja de esa fecha ya está cerrada, la venta se rechaza.
- Toda venta con un precio distinto del base queda registrada en la auditoría con ambos precios.

### 3.2 Editar una venta

- Solo si la caja de esa fecha sigue **abierta** y la venta no está anulada.
- Puedes cambiar productos, cantidades, precios, método de pago, fecha y observaciones. El sistema ajusta el stock solo por la diferencia y registra cada cambio en el historial.

### 3.3 Anular una venta (devolución)

*Solo Administrador.* En la fila de la venta pulsa **Anular**, escribe el motivo y confirma.

- Las unidades vuelven al inventario (quedan como **Devolución** en Movimientos).
- La venta deja de sumar en la caja y en los reportes, pero **no se borra**: queda marcada como *Anulada* con su motivo.
- Solo es posible con la caja de esa fecha abierta. Una venta anulada no se puede editar.

---

## 4. Cajas (cuadre diario)

*Menú: Operación diaria → Cajas. Solo Administrador.*

Cada caja representa un día: saldo inicial (base), ventas separadas por **Efectivo, Nequi, Transferencias y Tarjetas**, total de ventas, **gastos del día**, **retiros o consignaciones**, **saldo teórico** y, al cerrar, **saldo real** (dinero contado) y **diferencia**.

### 4.1 Abrir caja (Nueva caja)

1. Indica la **fecha** y el **saldo inicial** (dinero en caja al abrir).
2. Los totales de ventas y gastos del día se calculan solos.
3. Solo puede existir **una caja abierta por fecha**.

### 4.2 Cerrar caja (botón Cerrar caja)

1. En la fila de la caja abierta pulsa **Cerrar caja**.
2. Cuenta el dinero y escríbelo en **Dinero contado**.
3. Agrega observaciones si hace falta y confirma.

El sistema calcula:

- **Saldo teórico = Base + Ventas (Efectivo + Nequi + otros medios) − Gastos − Retiros**
- **Diferencia = dinero contado − saldo teórico**

Una diferencia **0** indica cuadre perfecto; **negativa**, que falta dinero; **positiva**, que sobra. Al cerrar, la caja queda bloqueada.

---

## 5. Gastos y egresos

*Menú: Operación diaria → Gastos. Solo Administrador.*

Registra lo que sale de caja: **materia prima, servicios, transporte u otros**, con fecha, descripción, categoría, monto y observaciones.

- Los gastos **restan del saldo teórico** de la caja de su fecha.
- No se pueden registrar, editar ni eliminar gastos de una fecha con caja cerrada.

### 5.1 Retiros y consignaciones

Cuando sale efectivo de la caja sin ser un gasto del negocio (por ejemplo, una **consignación al banco** o una entrega al dueño), regístralo en *Gastos → Nuevo gasto* con la categoría **Retiro / consignación**.

- Resta del **saldo teórico** (ese dinero ya no está en el cajón), así el cuadre da correcto.
- No se suma a los **gastos**: la caja, el Escritorio y el flujo de efectivo lo muestran aparte como *Retiros*.

---

## 6. Inventario

### 6.1 Productos

*Menú: Catálogo → Productos.*

El catálogo viene cargado con los **50 productos de la tienda** (19 helados, 16 bebidas, 8 aceites y 7 productos de coco) y sus precios de venta. El stock empieza en 0 y el costo vacío: el stock se fija con el primer conteo físico (sección 6.5) y el costo se completa editando cada producto.

Columnas: código, nombre, categoría, precios, **stock** (en rojo si está en el mínimo o por debajo), **stock calculado**, mínimo y estado.

- **Stock calculado** = inventario inicial + entradas − salidas, según el historial. Si aparece en rojo, no coincide con el stock registrado: revisa los movimientos de ese producto.
- **Crear o editar** (solo Administrador): categoría, nombre, descripción, precio de venta ($2.000–$95.000), precio de costo, stock, stock mínimo y estado. El código se genera solo si lo dejas vacío.
- Al crear un producto con stock, se registra su **inventario inicial**. Si cambias el stock al editar, se registra un **ajuste** automático.
- **Eliminar** (solo Administrador) saca el producto del catálogo sin perder sus ventas ni su historial. Para verlo de nuevo usa el filtro **Eliminados** y el botón **Restaurar**.
- **Desactivar** lo oculta de las ventas sin sacarlo del catálogo.

### 6.2 Categorías

*Menú: Catálogo → Categorías.*

La tienda trabaja con cuatro líneas: **Helados, Bebidas, Aceites y Productos de Coco**. Una categoría con productos no se puede eliminar.

### 6.3 Métodos de pago

*Menú: Catálogo → Métodos de Pago. Solo Administrador.*

Los canales son **Efectivo, Nequi, Transferencia y Tarjeta**. Un método desactivado desaparece del formulario de ventas; uno con ventas no se puede eliminar.

### 6.4 Movimientos (historial de inventario)

*Menú: Catálogo → Movimientos.*

Cada entrada o salida queda registrada con **fecha de la transacción**, fecha y hora exacta de registro, usuario, tipo, cantidad y **stock antes y después**. Filtra por **producto, tipo, usuario o rango de fechas**.

**Nuevo movimiento** (solo Administrador):
1. Elige el **tipo**, la **fecha y hora** de la transacción, el motivo y las observaciones.
2. Agrega **uno o varios productos** con su cantidad (siempre mayor a cero).
3. Guarda: todos los productos se registran juntos; si uno falla (por ejemplo, stock insuficiente en una merma), no se guarda ninguno.

Tipos: **Inventario inicial** (fija el stock base), **Compra**, **Devolución**, **Ajuste positivo**, **Ajuste negativo** y **Merma/Pérdida**. Las ventas y anulaciones aparecen solas. Los movimientos no se pueden editar ni eliminar.

### 6.5 Conteo físico (cierre de inventario)

*Menú: Catálogo → Conteo físico. Solo Administrador.* Reemplaza la columna de *inventario final* de la planilla.

1. Revisa la **fecha del conteo** y escribe en *Observaciones* quién contó o el turno.
2. Cuenta cada producto y escribe la cantidad en **Contado**; la **Diferencia** se calcula en el momento (verde si sobra, rojo si falta).
3. Deja vacío lo que no contaste: esos productos no se modifican.
4. Pulsa **Registrar conteo**. Cada sobrante queda como *Ajuste positivo* y cada faltante como *Ajuste negativo*, con el motivo «Conteo físico». Al final se muestra la diferencia valorizada a precio de venta.

> La **primera vez**, deja marcada la casilla **Es el inventario inicial de la tienda**: el conteo fija el stock (no calcula diferencias). Viene marcada mientras no haya movimientos.

---

## 7. Usuarios, roles y permisos

*Menú: Seguridad → Usuarios / Seguridad → Roles. Solo Administrador.*

### 7.1 Usuarios

- **Nombre**, **correo electrónico** (único, es el acceso), **contraseña** (mínimo 12 caracteres, con confirmación) y **roles**.
- Al editar, deja la contraseña vacía para no cambiarla.
- No puedes eliminar tu propio usuario.

### 7.2 Roles

- Los tres roles de la tienda vienen creados: **Admin, Operario y Consultor**, y no se pueden eliminar.
- Puedes crear roles nuevos con la combinación de permisos que necesites; las pantallas los respetan automáticamente.

---

## 8. Escritorio e informes

### 8.1 Escritorio

- Indicadores del día: ventas de hoy y del mes, ventas en efectivo, **Nequi**, transferencias y tarjetas, gastos (sin retiros) y valorización del inventario.
- **Productos con stock crítico:** lista de los productos en su mínimo o por debajo, con la **sugerencia de reorden** (unidades para volver al nivel objetivo).

### 8.2 Reportes

*Menú: Informes → Reportes. Administrador y Consultor.*

Elige el periodo (**Desde / Hasta**) y pulsa **Aplicar periodo**. Cada sección tiene botones **PDF**, **Excel** y **CSV** para descargarla.

| Sección | Contenido |
|---|---|
| Valorización de inventario | Valor del inventario a costo y a precio de venta, ganancia potencial |
| Indicadores de gestión | Precisión del inventario, pérdidas por mermas y ajustes, productos agotados, cajas con diferencia, ventas anuladas |
| Inventario por categoría | Productos, stock y valorización por línea |
| Productos con stock bajo | Con sugerencia de reorden |
| Comparativo | Periodo elegido frente al anterior de igual duración |
| Movimientos de inventario | Historial del periodo |
| Ventas diarias por producto | Unidades e ingreso de cada producto por día |
| Ventas por categoría / Más vendidos / Ingresos por día | Análisis de ventas del periodo |
| Flujo de efectivo | Del día *Hasta*: base, ventas por método, gastos, retiros o consignaciones, anuladas, saldo y, si la caja se cerró, lo contado y la diferencia |

Las ventas anuladas no se cuentan en ningún reporte de ventas.

### 8.3 Auditoría

*Menú: Informes → Auditoría. Solo Administrador.*

Registro de solo lectura de las operaciones críticas: ventas creadas, editadas y anuladas, precios distintos del base, cambios de inventario, cierres de caja y autorizaciones de datos. Filtra por acción, usuario o fecha.
