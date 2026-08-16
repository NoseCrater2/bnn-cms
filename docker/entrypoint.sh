#!/bin/sh

set -e

echo "Esperando a MySQL..."

until php artisan db:show > /dev/null 2>&1; do
    echo "MySQL todavía no está disponible..."
    sleep 2
done

echo "Ejecutando migraciones..."

php artisan migrate --seed --force

echo "Configurando storage link..."

if [ ! -L public/storage ]; then
    php artisan storage:link
fi

echo "Iniciando Laravel..."

exec "$@"
