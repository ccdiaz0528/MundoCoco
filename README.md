# MundoCoco

Sistema administrativo para una tienda: categorías, productos, ventas, métodos de pago y control diario de caja. Está construido con Laravel 13, Filament 5, PHP 8.3+ y Pest.

## Requisitos

- PHP 8.3 o superior con extensiones PDO y SQLite/MySQL.
- Composer 2.
- Node.js 20 o superior y npm.

## Instalación local

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
# Configura DB_CONNECTION y las credenciales de la base de datos en .env
php artisan migrate --seed
npm ci
npm run build
php artisan serve
```

El panel está disponible en `/admin`. Crea el usuario administrador por el mecanismo de tu entorno antes de publicar la aplicación.

## Reglas operativas

- Una venta toma el precio vigente del producto en el servidor; el navegador no decide importes ni subtotales.
- El stock se valida y descuenta dentro de transacciones con bloqueo de filas.
- Una caja abierta acumula ventas de `fecha_venta`; al cerrar, se registra el dinero contado y la diferencia.
- No se permiten ventas ni ediciones sobre fechas con caja cerrada.
- Las ventas y cajas no se borran desde el panel. Las correcciones deben realizarse antes del cierre o mediante un futuro flujo explícito de anulación.

## Calidad

```powershell
# Ejecutar pruebas
composer test

# Verificar estilo sin modificar archivos
vendor/bin/pint --test
```

Cada cambio se valida en GitHub Actions con estilo, pruebas y compilación de frontend.

## Estructura relevante

- `app/Services/`: reglas transaccionales de venta y caja.
- `app/Filament/`: interfaz administrativa.
- `app/Policies/`: autorización por rol.
- `database/migrations/`: estructura e índices de base de datos.
- `tests/`: pruebas de reglas de negocio y flujos.
