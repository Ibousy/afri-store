#!/bin/bash
set -e

# Fly.io fournit $PORT (souvent 8080), Railway fournit $PORT aussi
PORT=${PORT:-80}
sed -i "s/\${PORT}/$PORT/g" /etc/apache2/sites-available/000-default.conf
echo "Listen $PORT" > /etc/apache2/ports.conf

# Créer les dossiers uploads s'ils n'existent pas
mkdir -p /var/www/html/assets/images/uploads/products
mkdir -p /var/www/html/assets/images/uploads/videos
mkdir -p /var/www/html/logs
chown -R www-data:www-data /var/www/html/assets/images/uploads /var/www/html/logs

exec apache2-foreground
