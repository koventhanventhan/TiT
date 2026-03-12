#!/bin/sh

# Set the port in nginx config
sed -i "s/listen 80;/listen ${PORT:-80};/" /etc/nginx/sites-available/default

# Start PHP-FPM in the background
php-fpm -D

# Start Nginx in the foreground
nginx -g "daemon off;"
