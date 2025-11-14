#!/bin/bash
set -e

# Run Laravel optimizations if not already cached
if [ ! -f /var/www/html/bootstrap/cache/config.php ]; then
    echo "Running Laravel cache optimizations..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Start supervisord
exec "$@"
