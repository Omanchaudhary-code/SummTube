FROM php:8.2-apache

# Install system dependencies including git
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions including OPcache for production
RUN docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip opcache

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy existing application directory
COPY . .

# Clear any cached files and regenerate autoload
RUN rm -rf vendor/composer/autoload_*.php && \
    composer dump-autoload --optimize --no-dev --classmap-authoritative

# Create storage directories
RUN mkdir -p storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Set permissions (more secure: 755 instead of 775)
RUN chown -R www-data:www-data /var/www/html/storage \
    /var/www/html/bootstrap/cache && \
    chmod -R 755 /var/www/html/storage \
    /var/www/html/bootstrap/cache

# Enable Apache modules
RUN a2enmod rewrite headers deflate

# Configure OPcache for production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/opcache.ini

# Configure Apache security headers
RUN echo "Header always set X-Content-Type-Options nosniff" >> /etc/apache2/conf-available/security-headers.conf && \
    echo "Header always set X-Frame-Options DENY" >> /etc/apache2/conf-available/security-headers.conf && \
    echo "Header always set X-XSS-Protection \"1; mode=block\"" >> /etc/apache2/conf-available/security-headers.conf && \
    echo "Header always set Referrer-Policy \"strict-origin-when-cross-origin\"" >> /etc/apache2/conf-available/security-headers.conf && \
    echo "Header always set Permissions-Policy \"geolocation=(), microphone=(), camera=()\"" >> /etc/apache2/conf-available/security-headers.conf && \
    a2enconf security-headers

# Update Apache configuration
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf && \
    sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf && \
    echo "ServerName localhost" >> /etc/apache2/apache2.conf && \
    echo "ServerTokens Prod" >> /etc/apache2/apache2.conf && \
    echo "ServerSignature Off" >> /etc/apache2/apache2.conf

EXPOSE 80

CMD ["apache2-foreground"]