#!/bin/sh

# Esperar a que MySQL esté listo (opcional pero recomendable)
echo "⏳ Esperando a la base de datos..."
until mysql -h mysql -u root -prootsecret -e "SHOW DATABASES;" 2>/dev/null; do
  echo "⏳ Esperando a MySQL..."
  sleep 2
done

echo "✅ MySQL está listo!"

# Ejecutar migraciones
echo "🛠 Ejecutando migraciones..."
php artisan migrate --force

# Iniciar PHP-FPM
echo "🚀 Iniciando servidor Laravel..."
exec php artisan serve --host=0.0.0.0 --port=9000

