# This is a multi-stage build for optimized image size

# Stage 1: Dependencies
FROM composer:2 as composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Stage 2: Frontend build
FROM node:24 as frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 3: Runtime
FROM php:8.4-fpm-trixie

# Install system dependencies
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    default-mysql-client \
    postgresql-client \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    curl \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        pdo_pgsql \
        gd \
        zip \
        opcache \
        bcmath \
        pcntl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY resources/docker/php.ini $PHP_INI_DIR/conf.d/99-laravel.ini

# Configure Nginx
COPY resources/docker/nginx.conf /etc/nginx/nginx.conf
COPY resources/docker/default.conf /etc/nginx/sites-available/default
RUN ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default \
    && rm -f /etc/nginx/sites-enabled/default.bak

# Configure Supervisor
COPY resources/docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY --chown=www-data:www-data . .
COPY --from=composer /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

# Set permissions
RUN chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    && chmod -R 775 \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

# Optimize Laravel
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD curl -f http://localhost/up || exit 1

# Expose port
EXPOSE 80

# Start supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
