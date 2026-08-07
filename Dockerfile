FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libzip-dev unzip git \
    && docker-php-ext-install pdo pdo_mysql zip

WORKDIR /app
COPY . .

RUN mkdir -p storage/framework/{cache,cache/data,sessions,testing,views} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --optimize-autoloader --no-dev --no-interaction

EXPOSE 8080
CMD php artisan migrate --seed --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
