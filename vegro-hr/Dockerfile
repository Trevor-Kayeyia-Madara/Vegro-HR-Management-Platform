# syntax=docker/dockerfile:1

# Build Node.js assets stage
FROM node:22-alpine as assets

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm install

COPY . .
RUN npm run build

################################################################################

# Install PHP dependencies stage
FROM composer:lts as deps

WORKDIR /app

COPY composer.json composer.lock ./

RUN --mount=type=cache,target=/tmp/cache \
    composer install --no-dev --no-interaction --ignore-platform-reqs --no-scripts

################################################################################

# Final PHP-Apache runtime stage
FROM php:8.3-apache

WORKDIR /var/www/html

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libicu-dev \
    zip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    intl \
    json

# Enable Apache rewrite module for Laravel routing
RUN a2enmod rewrite headers

# Use production PHP configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Configure Apache document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy PHP dependencies from deps stage
COPY --from=deps /app/vendor ./vendor

# Copy Node.js assets from assets stage
COPY --from=assets /app/public/build ./public/build

# Copy application files
COPY --chown=www-data:www-data . .

# Ensure storage and bootstrap/cache directories are writable
RUN chmod -R 775 storage bootstrap/cache

# Use non-privileged user
USER www-data

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

CMD ["apache2-foreground"]
