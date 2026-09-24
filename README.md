# MundoCoco

Sistema administrativo para una tienda: categorías, productos, ventas, métodos de pago y control diario de caja. Está construido con Laravel 13, Filament 5, PHP 8.4+ y Pest.

## Requisitos

- PHP 8.4 o superior (el `composer.lock` usa Symfony 8) con extensiones PDO y SQLite/MySQL.
- Composer 2.
- Node.js 20 o superior y npm.

## Instalación local

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
# Configura DB_CONNECTION y las credenciales de la base de datos en .env
# Define también ADMIN_NAME, ADMIN_EMAIL y ADMIN_PASSWORD (usuario inicial con rol Admin)
php artisan migrate --seed
npm ci
npm run build
php artisan serve
```

El panel está disponible en `/admin`. El usuario administrador se crea con `AdminUserSeeder` a partir de `ADMIN_EMAIL`/`ADMIN_PASSWORD` del `.env` (sin esas variables no se crea nadie, por seguridad). En el primer ingreso cada usuario debe aceptar la política de tratamiento de datos (Ley 1581).

### Actualizar una base existente

```powershell
php artisan migrate
php artisan db:seed --class=MetodoPagoSeeder   # agrega Nequi
php artisan db:seed --class=RoleSeeder         # permisos RF10 (Operador → Operario)
```

## Reglas operativas

- El precio base sale del servidor; el vendedor puede aplicar otro precio (RF04) solo dentro del rango configurado ($2.000–$95.000) y queda auditado. Subtotal y total los calcula siempre el servidor.
- El stock se valida y descuenta dentro de transacciones con bloqueo de filas; todo cambio genera un movimiento trazable (RF12) y un registro de auditoría.
- Una caja abierta acumula ventas de `fecha_venta`, separadas por Efectivo, Nequi, Transferencia y Tarjeta; al cerrar, se registra el dinero contado y la diferencia.
- No se permiten ventas, ediciones ni anulaciones sobre fechas con caja cerrada.
- Las ventas y cajas no se borran. Una venta se corrige editándola o se **anula** (devuelve el stock y sale de caja y reportes); los productos se eliminan de forma lógica y se pueden restaurar.
- Roles (RF10): Administrador (todo), Operario (ventas y consulta de inventario), Consultor (solo reportes).

## Calidad

```powershell
vendor/bin/pint --test     # estilo
composer test              # pruebas
npm run build              # assets
composer test:cobertura    # RNF10: ≥ 80 % en código crítico (requiere pcov o xdebug)
```

Cada cambio se valida en GitHub Actions con estilo, pruebas, cobertura y compilación de frontend.

## Estructura relevante

- `app/Services/`: reglas transaccionales de venta, inventario, caja y reportes.
- `app/Filament/`: interfaz administrativa.
- `app/Policies/`: autorización por permisos (RF10).
- `config/mundococo.php`: parámetros de negocio configurables (RNF06).
- `routes/api.php`: API de integración de solo lectura (RNF07).
- `database/migrations/`: estructura e índices de base de datos.
- `tests/`: pruebas de reglas de negocio y flujos; `tests/Feature/CumplimientoAnteproyectoTest.php` verifica el anteproyecto.
- `docs/`: `MANUAL_USUARIO.md`, `MANUAL_TECNICO.md`, `CUMPLIMIENTO_ANTEPROYECTO.md`, `IMPLEMENTACION.md`, `TESTING_REPORT.md`.
- `DOCS DE PROYECTO/`: entregables académicos (anteproyecto, RF/RNF, presentaciones).
