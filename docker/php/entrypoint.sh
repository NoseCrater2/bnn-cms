#!/bin/sh

set -e

echo "Esperando a MySQL..."

until php artisan db:show > /dev/null 2>&1; do
    echo "MySQL todavía no está disponible..."
    sleep 2
done

echo "Ejecutando migraciones..."

php artisan migrate --seed --force

echo "Iniciando Laravel..."

exec "$@"
