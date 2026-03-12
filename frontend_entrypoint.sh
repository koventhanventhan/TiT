#!/bin/sh
# Generate the actual nginx config from the template
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf

# Start Nginx in the foreground
nginx -g "daemon off;"
