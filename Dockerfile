FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libzip-dev unzip git \
    && docker-php-ext-install pdo pdo_mysql zip

WORKDIR /app
COPY . .

RUN mkdir -p storage/framework/{cache,cache/data,sessions,testing,views} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# A Railway volume mounted at storage/app/public shadows the git-tracked seed
# images baked into this layer, so keep a copy outside the mount point and
# restore it at boot (see CMD) without clobbering anything already on the volume.
RUN mkdir -p /app/seed-storage && cp -r storage/app/public/. /app/seed-storage/

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --optimize-autoloader --no-dev --no-interaction
RUN php artisan storage:link

EXPOSE 8080
CMD mkdir -p storage/app/public && cp -rn /app/seed-storage/. storage/app/public/ && php artisan migrate --seed --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
