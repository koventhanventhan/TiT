# Use PHP 8.2 CLI for a lightweight production image
FROM php:8.2-cli-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    libxml2-dev \
    icu-dev \
    zip \
    unzip \
    git \
    curl

# Install PHP extensions required by Laravel
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

# Install Composer from the official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files first to leverage Docker cache
COPY composer.json composer.lock ./

# Install dependencies without dev tools and optimize autoloader
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copy the rest of the application
COPY . .

# Set permissions for Laravel storage and cache
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache && \
    chmod -R 775 /app/storage /app/bootstrap/cache

# Generate optimized configuration and route cache
# Note: Key generation should ideally happen via environment variables in production
# RUN php artisan key:generate --force

# Expose the port Render expects (10000)
EXPOSE 10000

# Start Laravel using artisan serve as requested
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=10000"]
