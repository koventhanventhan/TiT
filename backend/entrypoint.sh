#!/bin/sh

# Set the port in nginx config
sed -i "s/listen 80;/listen ${PORT:-80};/" /etc/nginx/conf.d/default.conf

# Start PHP-FPM in the background
php-fpm -D

# Run database migrations
php artisan migrate --force

# Start Nginx in the foreground
nginx -g "daemon off;"
