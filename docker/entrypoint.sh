#!/bin/sh
set -e
# Render inyecta PORT; Apache debe escuchar ahí
PORT="${PORT:-80}"
sed -ri "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

cd /var/www/html
php artisan config:clear --ansi
php artisan migrate --force --seed --ansi
php artisan config:cache --ansi
php artisan route:cache --ansi
php artisan view:cache --ansi
php artisan filament:cache-components --ansi 2>/dev/null || true

# RNF04: backups diarios y limpieza de auditoría (routes/console.php).
php artisan schedule:work >> storage/logs/scheduler.log 2>&1 &

chown -R www-data:www-data storage bootstrap/cache
exec apache2-foreground
