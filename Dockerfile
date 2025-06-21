FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libpq-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    libmemcached-dev \
    zlib1g-dev \
    libssl-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip opcache

# Configure GD
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Memcached extension
RUN pecl install memcached && docker-php-ext-enable memcached

# Install APCu
RUN pecl install apcu && docker-php-ext-enable apcu

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Configure PHP-FPM to run as www-data (default)
RUN sed -i 's/^user = .*/user = www-data/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/^group = .*/group = www-data/' /usr/local/etc/php-fpm.d/www.conf

# Copy application files
COPY --chown=www-data:www-data ./src /var/www/html

# Create necessary directories and set permissions
RUN mkdir -p storage/app/public \
    storage/app/private \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    /var/www/.config/psysh && \
    chmod -R 775 storage bootstrap/cache && \
    chmod -R 755 /var/www/.config && \
    chown -R www-data:www-data storage bootstrap/cache /var/www/.config

# Run as www-data
USER www-data