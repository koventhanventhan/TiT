#!/bin/bash

# Port set for Apache
# Railway injects $PORT, we replace it in ports.conf and our site config
sed -i "s/Listen 80/Listen ${PORT:-8000}/" /etc/apache2/ports.conf
sed -i "s/\${PORT}/${PORT:-8000}/" /etc/apache2/sites-available/000-default.conf

# Laravel setup
php artisan config:cache
php artisan route:cache
php artisan migrate --force

# Start Apache in the foreground
apache2-foreground
