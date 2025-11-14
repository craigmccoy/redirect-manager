#!/bin/bash
set -e

echo "Starting Laravel application setup..."

# Set permissions at runtime
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create storage link if it doesn't exist
if [ ! -L /var/www/html/public/storage ]; then
    echo "Creating storage symbolic link..."
    php artisan storage:link
fi

# Wait for database to be ready
echo "Waiting for database connection..."
RETRIES=30
until php artisan db:show 2>/dev/null || [ $RETRIES -eq 0 ]; do
    echo "Database not ready yet. Retrying... ($RETRIES attempts remaining)"
    RETRIES=$((RETRIES-1))
    sleep 2
done

if [ $RETRIES -eq 0 ]; then
    echo "ERROR: Failed to connect to database after multiple attempts"
    exit 1
fi

echo "Database connection established!"

# Handle queue worker container
if [ "${CONTAINER_ROLE}" = "queue" ]; then
    echo "Starting queue worker..."
    exec php artisan queue:work --tries=3 --timeout=90
fi

# Handle scheduler container
if [ "${CONTAINER_ROLE}" = "scheduler" ]; then
    echo "Starting scheduler..."
    exec php artisan schedule:work
fi

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Environment-specific setup
if [ "${APP_ENV}" = "production" ]; then
    echo "Running production optimizations..."
    
    # Clear old caches first
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    
    # Cache configuration, routes, and views
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    echo "Production optimizations complete!"
else
    echo "Running in ${APP_ENV} mode - skipping cache optimizations"
    
    # Clear caches in development
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
fi

echo "Laravel application setup complete!"

# Start the main process (e.g., supervisord, php-fpm, etc.)
exec "$@"
