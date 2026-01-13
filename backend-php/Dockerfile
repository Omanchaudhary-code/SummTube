FROM php:8.2-fpm
RUN apt-get update && apt-get install -y git curl libpng-dev libonig-dev libxml2-dev libzip-dev libpq-dev zip unzip nginx supervisor \
 && apt-get clean && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install pdo_pgsql pgsql pdo_mysql mbstring exif pcntl bcmath gd zip opcache
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts
COPY . .
RUN mkdir -p storage/logs && chown -R www-data:www-data storage && chmod -R 755 storage
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
EXPOSE 8080
CMD ["sh","-c","sed -i \"s/listen 8080;/listen ${PORT};/\" /etc/nginx/conf.d/default.conf && php-fpm -F & nginx -g 'daemon off;'"]
