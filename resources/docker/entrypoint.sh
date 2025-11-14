#!/bin/bash
set -e

# Set permissions at runtime
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Run Laravel optimizations if not already cached
if [ ! -f /var/www/html/bootstrap/cache/config.php ]; then
    echo "Running Laravel cache optimizations..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Start supervisord
exec "$@"
