#!/bin/sh
# Se ejecuta cada vez que arranca el servidor. Si algo falla, no arranca a medias.
set -e

# Guarda la configuracion y las rutas ya procesadas, con las variables de Render
php artisan config:cache
php artisan route:cache

# Solo aplica las migraciones nuevas: nunca borra datos ni crea usuarios de prueba
php artisan migrate --force

chown -R www-data:www-data storage bootstrap/cache

exec apache2-foreground
