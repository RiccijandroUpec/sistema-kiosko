#!/bin/sh
set -e

echo "Ejecutando migraciones"
php artisan migrate --force
php artisan config:cache

echo "Iniciando supervisord (web + worker + scheduler)"
exec supervisord -n -c /app/docker/supervisord.conf
