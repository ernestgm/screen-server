#!/bin/sh

# Ejecutar migraciones
echo "🛠 Ejecutando migraciones..."
php artisan migrate --force

# Iniciar PHP-FPM
echo "🚀 Iniciando servidor Laravel..."
exec php artisan serve --host=0.0.0.0 --port=9000

