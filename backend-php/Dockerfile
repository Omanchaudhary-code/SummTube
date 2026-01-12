# =========================
# Base image
# =========================
FROM php:8.2-apache

# =========================
# System dependencies
# =========================
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

# =========================
# PHP extensions
# =========================
RUN docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache

# =========================
# Composer
# =========================
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# =========================
# Working directory
# =========================
WORKDIR /var/www/html

# =========================
# Copy composer files first (better caching)
# =========================
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# =========================
# Copy application source
# =========================
COPY . .

# =========================
# Laravel permissions
# =========================
RUN mkdir -p storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 755 storage bootstrap/cache

# =========================
# Apache configuration
# =========================
RUN a2enmod rewrite headers deflate

# Point Apache to Laravel public folder
RUN sed -i 's!/var/www/html!/var/www/html/public!g' \
    /etc/apache2/sites-available/000-default.conf

# Allow .htaccess
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/s/AllowOverride None/AllowOverride All/' \
    /etc/apache2/apache2.conf

# Security headers
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf \
 && echo "ServerTokens Prod" >> /etc/apache2/apache2.conf \
 && echo "ServerSignature Off" >> /etc/apache2/apache2.conf \
 && echo "Header always set X-Content-Type-Options nosniff" >> /etc/apache2/conf-available/security-headers.conf \
 && echo "Header always set X-Frame-Options DENY" >> /etc/apache2/conf-available/security-headers.conf \
 && echo "Header always set X-XSS-Protection \"1; mode=block\"" >> /etc/apache2/conf-available/security-headers.conf \
 && echo "Header always set Referrer-Policy \"strict-origin-when-cross-origin\"" >> /etc/apache2/conf-available/security-headers.conf \
 && echo "Header always set Permissions-Policy \"geolocation=(), microphone=(), camera=()\"" >> /etc/apache2/conf-available/security-headers.conf \
 && a2enconf security-headers

# =========================
# OPcache (production)
# =========================
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/opcache.ini

# =========================
# 🔴 Render dynamic PORT fix (CRITICAL)
# =========================
RUN sed -i 's/80/${PORT}/g' /etc/apache2/ports.conf \
 && sed -i 's/:80/:${PORT}/g' /etc/apache2/sites-available/000-default.conf

EXPOSE ${PORT}

# =========================
# Start Apache
# =========================
CMD ["apache2-foreground"]
