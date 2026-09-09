# Manual de Usuario — Sistema de Gestión MundoCoco

> Aplicación web para el control de inventario, ventas y caja de la tienda MundoCoco.
> Panel de administración: `http://tu-servidor/admin` · Interfaz completamente en español.

---

## 1. Ingreso al sistema

1. Abre el navegador (Chrome, Edge, Firefox o Safari) y entra a `/admin`.
2. Escribe tu **correo electrónico** y tu **contraseña**.
3. Pulsa **Iniciar sesión**.

- La sesión se cierra sola después de 2 horas sin actividad.
- Si olvidas tu contraseña, pide al administrador que te asigne una nueva desde **Seguridad → Usuarios**.
- La página de **Política de tratamiento de datos** está disponible en `/privacidad`.

### 1.1 Roles de usuario

| Rol | Qué puede hacer |
|-----|-----------------|
| **Administrador** | Todo: catálogo, operación diaria, informes, usuarios, roles y permisos. |
| **Operador** | Registrar ventas, abrir y cerrar cajas, registrar gastos y movimientos, consultar inventario e informes. No gestiona usuarios ni roles. |
| **Consultor** | Solo consulta: inventario, ventas, cajas, gastos, movimientos e informes. No puede crear ni editar nada. |

---

## 2. Recorrido diario recomendado

Este es el flujo de trabajo de un día normal en la tienda:

1. **Abrir la caja** — *Operación diaria → Cajas → Nueva caja*: indica la fecha de hoy y el dinero base con el que arrancas (saldo inicial).
2. **Registrar entradas de mercancía** — *Catálogo → Movimientos → Nuevo movimiento*: compras al proveedor, devoluciones o ajustes (ver sección 6).
3. **Registrar cada venta** — *Operación diaria → Ventas → Nueva venta* (ver sección 3).
4. **Registrar los gastos del día** — *Operación diaria → Gastos → Nuevo gasto*: materia prima, transporte, servicios (ver sección 5).
5. **Revisar el día** — *Informes → Reportes* y el *Escritorio* (ver sección 8).
6. **Cerrar la caja** — en *Cajas*, botón **Cerrar caja**: cuenta el dinero físico, escríbelo como *dinero contado* y confirma. El sistema calcula si sobra o falta (ver sección 4).

> Regla de oro: **una fecha con caja cerrada queda bloqueada**. Ya no se pueden registrar ventas, gastos ni ediciones de ese día. Revisa bien antes de cerrar.

---

## 3. Ventas

*Menú: Operación diaria → Ventas.*

Cada fila muestra: número de venta, **método de pago** (Efectivo, Transferencia o Tarjeta), total, observaciones y fecha. Usa el filtro por método de pago para conciliar cada canal.

### 3.1 Registrar una venta (Nueva venta)

1. Elige el **método de pago** (solo aparecen los activos).
2. Escribe observaciones si lo necesitas (opcional).
3. Pulsa **+ Agregar producto**, elige el producto e indica la **cantidad**.
4. El **precio lo pone el sistema** automáticamente con el precio de venta vigente del producto; el subtotal y el total se calculan solos. Lo que escriba el navegador nunca decide el valor final: al guardar, el servidor vuelve a leer el precio.
5. Guarda. El stock se descuenta de inmediato y la venta queda sumada en la caja del día.

Reglas importantes:
- La venta queda registrada con la **fecha y hora actual**; no se puede cambiar.
- No se puede vender más unidades de las que hay en stock (ni siquiera repartidas en varias líneas del mismo producto).
- No se pueden vender productos **inactivos**.
- Si la caja de hoy ya está cerrada, la venta se rechaza.
- Las ventas **no se pueden eliminar** desde el panel. Si hay un error, corrígelo con **Editar** antes del cierre de caja.

### 3.2 Editar una venta

- Solo es posible si la caja de esa fecha sigue **abierta**.
- Al guardar, el sistema devuelve el stock anterior, aplica las nuevas cantidades, recalcula precios con los valores vigentes y actualiza la caja.

---

## 4. Cajas (cuadre diario)

*Menú: Operación diaria → Cajas.*

Cada caja representa un día: saldo inicial (base), ventas separadas por **Efectivo / Transferencias / Tarjetas**, total de ventas, **gastos del día**, **saldo teórico** y, al cerrar, **saldo real** (dinero contado) y **diferencia**.

### 4.1 Abrir caja (Nueva caja)

1. Indica la **fecha** y el **saldo inicial** (dinero en caja al abrir).
2. Los totales de ventas y gastos del día se calculan solos si ya hay movimientos de esa fecha.
3. Solo puede existir **una caja abierta por fecha**.

### 4.2 Cerrar caja (botón Cerrar caja)

1. En la fila de la caja abierta pulsa **Cerrar caja**.
2. Cuenta todo el dinero físico y escríbelo en **Dinero contado**.
3. Agrega observaciones del cierre si hace falta y confirma.

El sistema calcula:

- **Saldo teórico = saldo inicial + total de ventas − total de gastos**
- **Diferencia = dinero contado − saldo teórico**

Interpretación de la diferencia:
- **0** (verde): el cuadre está perfecto.
- **Negativa** (rojo): falta dinero.
- **Positiva** (amarillo): sobra dinero.

Al cerrar, la caja queda bloqueada: no se puede editar ni recibir más ventas o gastos de esa fecha. Solo el botón **Editar** de cajas abiertas sigue disponible.

---

## 5. Gastos y egresos

*Menú: Operación diaria → Gastos.*

Registra aquí todo lo que sale de caja: **materia prima, servicios, transporte u otros**. Cada gasto pide fecha, descripción, categoría, monto y observaciones opcionales.

- Los gastos del día **restan en el saldo teórico** de la caja automáticamente.
- No se pueden registrar ni editar gastos de una fecha con **caja cerrada**.
- Solo el Administrador puede eliminar un gasto, y únicamente si su caja sigue abierta.

---

## 6. Inventario

### 6.1 Productos

*Menú: Catálogo → Productos.*

Columnas: nombre, categoría, **precio de venta**, precio de costo, **stock** (en rojo si está en mínimo o por debajo), mínimo y estado activo/inactivo. Filtra por categoría desde el desplegable.

**Crear o editar un producto** (botón *Nuevo producto*):
- Categoría, nombre, descripción (opcional), precio de venta, precio de costo (opcional), **stock actual**, **stock mínimo** (nivel de alerta) y estado activo.
- Al crear un producto con stock mayor a cero, el sistema registra automáticamente su **inventario inicial** en el historial.
- Si cambias el stock manualmente al editar, el sistema registra un **ajuste** automático en el historial.
- Desactivar un producto lo oculta de las ventas sin borrar su historial.
- Un producto con ventas registradas **no se puede eliminar** (primero debe gestionarlo el Administrador).

**Alertas de stock mínimo:** cuando el stock llega al mínimo o lo baja, el producto se marca en rojo en la lista, aparece en el contador del Escritorio y en los informes con sugerencia de reposición.

### 6.2 Categorías

*Menú: Catálogo → Categorías.*

La tienda trabaja con cuatro líneas: **Helados, Bebidas, Aceites y Productos de Coco**. Puedes crear, editar y ver categorías con su descripción y número de productos. Una categoría con productos **no se puede eliminar**.

### 6.3 Métodos de pago

*Menú: Catálogo → Métodos de Pago.*

Los canales son **Efectivo, Transferencia y Tarjeta**. Puedes crear nuevos, editarlos o desactivarlos. Un método desactivado **desaparece del formulario de ventas**, y uno con ventas registradas no se puede eliminar. La columna *Ventas* indica cuántas ventas usaron cada método.

### 6.4 Movimientos (historial de inventario)

*Menú: Catálogo → Movimientos.*

Es la **trazabilidad completa**: cada entrada o salida de mercancía queda registrada con fecha y hora, usuario que la hizo, tipo, cantidad y **stock antes y después**. Filtra por tipo o por producto.

**Nuevo movimiento:** elige producto, tipo y cantidad (siempre positiva y mayor a cero):
- **Inventario Inicial**: fija el stock base del producto.
- **Compra / Entrada**: suma mercancía del proveedor.
- **Devolución**: suma devoluciones de clientes.
- **Ajuste positivo / Ajuste negativo**: correcciones manuales.
- **Merma / Pérdida**: restas por vencimiento, daño o pérdida.
- Las **ventas** aparecen aquí automáticamente; no se crean a mano (el sistema lo impide).

Los movimientos **no se pueden editar ni eliminar**: son el historial oficial del inventario.

---

## 7. Usuarios, roles y permisos

*Menú: Seguridad → Usuarios / Seguridad → Roles (solo Administrador).*

### 7.1 Usuarios (Nuevo usuario)

- **Nombre**, **correo electrónico** (único, es tu acceso), **contraseña** (mínimo 12 caracteres, con confirmación) y **roles**.
- Al editar, deja la contraseña vacía si no quieres cambiarla.
- La ficha de cada usuario (botón **Ver**) muestra sus datos, roles y fechas.
- No puedes eliminar tu propio usuario.

### 7.2 Roles (Nuevo rol)

- Cada rol tiene **nombre** y una lista de **permisos** (ventas, caja, gastos, movimientos, informes, usuarios…).
- Los tres roles de la tienda ya vienen creados: **Administrador, Operador y Consultor**. Puedes ajust
...[truncated 3880 chars]