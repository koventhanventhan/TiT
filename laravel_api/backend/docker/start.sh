#!/bin/sh

# Laravel setup
php artisan config:cache
php artisan route:cache
php artisan migrate --force

# Nginx port replace
# Note: envsubst is used to replace ${PORT} in the config
envsubst '${PORT}' < /etc/nginx/nginx.conf > /etc/nginx/nginx.conf.tmp
mv /etc/nginx/nginx.conf.tmp /etc/nginx/nginx.conf

# Start PHP-FPM and Nginx
php-fpm -D
nginx -g 'daemon off;'
