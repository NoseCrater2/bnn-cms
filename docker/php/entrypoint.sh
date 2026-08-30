#!/bin/sh

set -e

echo "Esperando a MySQL..."

until php artisan db:show > /dev/null 2>&1; do
    echo "MySQL todavía no está disponible..."
    sleep 2
done

echo "Ejecutando migraciones..."

php artisan migrate --force

echo "Optimizando Laravel para el entorno local..."

composer install --no-interaction --prefer-dist --optimize-autoloader
php artisan optimize

echo "Iniciando Laravel..."

exec "$@"
